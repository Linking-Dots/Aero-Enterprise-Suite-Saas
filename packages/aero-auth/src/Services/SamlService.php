<?php

namespace Aero\Auth\Services;

use Aero\Auth\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OneLogin\Saml2\Auth as Saml2Auth;

/**
 * SamlService - SAML 2.0 Single Sign-On Service
 *
 * Provides SAML authentication support for both standalone and SaaS modes.
 * In SaaS mode, can use tenant-specific IdP configurations.
 * In standalone mode, uses global IdP configuration only.
 */
class SamlService
{
    protected ?Saml2Auth $auth = null;

    protected array $config;

    protected ?string $idpName = null;

    public function __construct()
    {
        $this->config = config('saml2') ?? [];
    }

    /**
     * Check if SAML is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->config['enabled'] ?? false;
    }

    /**
     * Initialize SAML auth for a specific IdP.
     *
     * @param  string  $idpName  The IdP name to use
     * @param  mixed  $tenant  Optional tenant model for tenant-specific IdP config (SaaS mode)
     */
    public function initAuth(string $idpName = 'default', mixed $tenant = null): Saml2Auth
    {
        $this->idpName = $idpName;

        $settings = $this->buildSettings($idpName, $tenant);
        $this->auth = new Saml2Auth($settings);

        return $this->auth;
    }

    /**
     * Build SAML settings array for OneLogin library.
     *
     * @param  mixed  $tenant  Optional tenant model for tenant-specific IdP config
     */
    protected function buildSettings(string $idpName, mixed $tenant = null): array
    {
        // Check for tenant-specific IdP configuration
        $idpConfig = $this->getIdpConfig($idpName, $tenant);

        if (! $idpConfig) {
            throw new \Exception("SAML IdP configuration not found: {$idpName}");
        }

        $spConfig = $this->config['sp'];
        $securityConfig = $this->config['security'];

        return [
            'strict' => ! $this->config['debug'],
            'debug' => $this->config['debug'],
            'baseurl' => config('app.url'),
            'sp' => [
                'entityId' => $spConfig['entityId'],
                'assertionConsumerService' => $spConfig['assertionConsumerService'],
                'singleLogoutService' => $spConfig['singleLogoutService'],
                'NameIDFormat' => $spConfig['NameIDFormat'],
                'x509cert' => $spConfig['x509cert'] ?? '',
                'privateKey' => $spConfig['privateKey'] ?? '',
            ],
            'idp' => [
                'entityId' => $idpConfig['entityId'],
                'singleSignOnService' => $idpConfig['singleSignOnService'],
                'singleLogoutService' => $idpConfig['singleLogoutService'] ?? null,
                'x509cert' => $idpConfig['x509cert'] ?? '',
                'certFingerprint' => $idpConfig['certFingerprint'] ?? '',
                'certFingerprintAlgorithm' => $idpConfig['certFingerprintAlgorithm'] ?? 'sha256',
            ],
            'security' => $securityConfig,
        ];
    }

    /**
     * Get IdP configuration, checking tenant overrides first.
     *
     * @param  mixed  $tenant  Optional tenant model for tenant-specific config
     */
    protected function getIdpConfig(string $idpName, mixed $tenant = null): ?array
    {
        // Check tenant-specific configuration
        if ($tenant) {
            $tenantIdp = $tenant->data['saml_idp'] ?? null;
            if ($tenantIdp && ($tenantIdp['name'] ?? null) === $idpName) {
                return $tenantIdp;
            }
        }

        // Fall back to global configuration
        return $this->config['idps'][$idpName] ?? null;
    }

    /**
     * Initiate SSO login redirect.
     *
     * @param  mixed  $tenant  Optional tenant model for tenant-specific IdP config
     */
    public function login(string $idpName = 'default', ?string $returnTo = null, mixed $tenant = null): string
    {
        $this->initAuth($idpName, $tenant);

        return $this->auth->login($returnTo, [], false, false, true);
    }

    /**
     * Process the SAML response after IdP authentication.
     *
     * @param  mixed  $tenant  Optional tenant model for tenant-specific config
     * @return array{user: User, created: bool}|null
     */
    public function processResponse(string $idpName = 'default', mixed $tenant = null): ?array
    {
        $this->initAuth($idpName, $tenant);

        $this->auth->processResponse();

        $errors = $this->auth->getErrors();
        if (! empty($errors)) {
            Log::error('SAML Response Errors', [
                'errors' => $errors,
                'last_error_reason' => $this->auth->getLastErrorReason(),
            ]);

            return null;
        }

        if (! $this->auth->isAuthenticated()) {
            Log::warning('SAML authentication failed - not authenticated');

            return null;
        }

        // Get user attributes
        $attributes = $this->auth->getAttributes();
        $nameId = $this->auth->getNameId();

        Log::debug('SAML Attributes received', [
            'nameId' => $nameId,
            'attributes' => $attributes,
        ]);

        // Map attributes to user data
        $userData = $this->mapAttributes($attributes, $nameId);

        // Find or create user
        return $this->findOrCreateUser($userData, $tenant);
    }

    /**
     * Map SAML attributes to user fields.
     */
    protected function mapAttributes(array $attributes, string $nameId): array
    {
        $mapping = $this->config['attribute_mapping'];
        $userData = ['saml_name_id' => $nameId];

        foreach ($mapping as $field => $samlAttributes) {
            foreach ((array) $samlAttributes as $samlAttr) {
                if (isset($attributes[$samlAttr])) {
                    $value = $attributes[$samlAttr];
                    $userData[$field] = is_array($value) ? $value[0] : $value;
                    break;
                }
            }
        }

        // Ensure email is set (use nameId if email attribute not found)
        if (empty($userData['email']) && filter_var($nameId, FILTER_VALIDATE_EMAIL)) {
            $userData['email'] = $nameId;
        }

        // Build name from first_name + last_name if name not provided
        if (empty($userData['name']) && (! empty($userData['first_name']) || ! empty($userData['last_name']))) {
            $userData['name'] = trim(($userData['first_name'] ?? '').' '.($userData['last_name'] ?? ''));
        }

        return $userData;
    }

    /**
     * Find or create user from SAML data.
     *
     * @param  mixed  $tenant  Optional tenant model
     * @return array{user: User, created: bool}
     */
    protected function findOrCreateUser(array $userData, mixed $tenant = null): array
    {
        if (empty($userData['email'])) {
            throw new \Exception('Email is required for SAML authentication');
        }

        $created = false;

        // Find existing user
        $user = User::where('email', $userData['email'])->first();

        if (! $user && $this->config['auto_provision']) {
            // Create new user
            $user = $this->createUser($userData, $tenant);
            $created = true;
        } elseif ($user) {
            // Update existing user with SAML data
            $this->updateUserFromSaml($user, $userData);
        }

        if (! $user) {
            throw new \Exception('User not found and auto-provisioning is disabled');
        }

        return ['user' => $user, 'created' => $created];
    }

    /**
     * Create a new user from SAML data.
     *
     * @param  mixed  $tenant  Optional tenant model for tenant_id assignment
     */
    protected function createUser(array $userData, mixed $tenant = null): User
    {
        $user = new User;
        $user->email = $userData['email'];
        $user->name = $userData['name'] ?? $userData['email'];
        $user->password = Hash::make(Str::random(32)); // Random password since they use SSO
        $user->email_verified_at = now();

        // Set optional fields
        if (! empty($userData['first_name'])) {
            $user->first_name = $userData['first_name'];
        }
        if (! empty($userData['last_name'])) {
            $user->last_name = $userData['last_name'];
        }

        // Store SAML-specific data
        $user->saml_id = $userData['saml_name_id'];
        $user->auth_provider = 'saml:'.$this->idpName;

        // Set tenant if provided (only for landlord users with tenant_id field)
        if ($tenant && \Schema::hasColumn($user->getTable(), 'tenant_id')) {
            $user->tenant_id = $tenant->id;
        }

        $user->save();

        // Assign default role
        $defaultRole = $this->config['default_role'];
        if ($defaultRole) {
            $user->assignRole($defaultRole);
        }

        Log::info('SAML user created', [
            'user_id' => $user->id,
            'email' => $user->email,
            'idp' => $this->idpName,
        ]);

        return $user;
    }

    /**
     * Update existing user with SAML data.
     */
    protected function updateUserFromSaml(User $user, array $userData): void
    {
        $updated = false;

        // Update SAML ID if not set
        if (empty($user->saml_id) && ! empty($userData['saml_name_id'])) {
            $user->saml_id = $userData['saml_name_id'];
            $updated = true;
        }

        // Update auth provider
        if ($user->auth_provider !== 'saml:'.$this->idpName) {
            $user->auth_provider = 'saml:'.$this->idpName;
            $updated = true;
        }

        // Optionally update name if changed
        if (! empty($userData['name']) && $user->name !== $userData['name']) {
            $user->name = $userData['name'];
            $updated = true;
        }

        if ($updated) {
            $user->save();
        }
    }

    /**
     * Initiate single logout.
     *
     * @param  mixed  $tenant  Optional tenant model for tenant-specific config
     */
    public function logout(
        string $idpName = 'default',
        ?string $returnTo = null,
        ?string $nameId = null,
        ?string $sessionIndex = null,
        mixed $tenant = null
    ): ?string {
        $this->initAuth($idpName, $tenant);

        return $this->auth->logout($returnTo, [], $nameId, $sessionIndex, true);
    }

    /**
     * Process single logout response.
     *
     * @param  mixed  $tenant  Optional tenant model
     */
    public function processSlo(string $idpName = 'default', mixed $tenant = null): bool
    {
        $this->initAuth($idpName, $tenant);

        $this->auth->processSLO();

        $errors = $this->auth->getErrors();
        if (! empty($errors)) {
            Log::error('SAML SLO Errors', ['errors' => $errors]);

            return false;
        }

        return true;
    }

    /**
     * Get SP metadata XML.
     *
     * @param  mixed  $tenant  Optional tenant model
     */
    public function getMetadata(string $idpName = 'default', mixed $tenant = null): string
    {
        $this->initAuth($idpName, $tenant);
        $settings = $this->auth->getSettings();

        return $settings->getSPMetadata();
    }

    /**
     * Get available IdPs for a tenant.
     *
     * @param  mixed  $tenant  Optional tenant model
     */
    public function getAvailableIdps(mixed $tenant = null): array
    {
        $idps = [];

        // Add global IdPs that are configured
        foreach ($this->config['idps'] as $name => $config) {
            if (! empty($config['entityId']) && ! empty($config['singleSignOnService']['url'])) {
                $idps[$name] = [
                    'name' => $name,
                    'label' => $this->getIdpLabel($name),
                    'icon' => $this->getIdpIcon($name),
                ];
            }
        }

        // Add tenant-specific IdP
        if ($tenant && ! empty($tenant->data['saml_idp'])) {
            $tenantIdp = $tenant->data['saml_idp'];
            $idps[$tenantIdp['name']] = [
                'name' => $tenantIdp['name'],
                'label' => $tenantIdp['label'] ?? 'Enterprise SSO',
                'icon' => 'building',
            ];
        }

        return $idps;
    }

    /**
     * Get human-readable label for IdP.
     */
    protected function getIdpLabel(string $name): string
    {
        return match ($name) {
            'azure' => 'Microsoft Entra ID',
            'okta' => 'Okta',
            'google' => 'Google Workspace',
            'onelogin' => 'OneLogin',
            default => ucfirst($name).' SSO',
        };
    }

    /**
     * Get icon for IdP.
     */
    protected function getIdpIcon(string $name): string
    {
        return match ($name) {
            'azure' => 'microsoft',
            'okta' => 'okta',
            'google' => 'google',
            'onelogin' => 'onelogin',
            default => 'key',
        };
    }

    /**
     * Validate IdP configuration.
     */
    public function validateIdpConfig(array $config): array
    {
        $errors = [];

        if (empty($config['entityId'])) {
            $errors[] = 'Entity ID is required';
        }

        if (empty($config['singleSignOnService']['url'])) {
            $errors[] = 'SSO URL is required';
        }

        if (empty($config['x509cert']) && empty($config['certFingerprint'])) {
            $errors[] = 'Either X.509 certificate or certificate fingerprint is required';
        }

        return $errors;
    }

    /**
     * Save tenant-specific IdP configuration.
     */
    public function saveTenantIdpConfig(Tenant $tenant, array $config): bool
    {
        $errors = $this->validateIdpConfig($config);

        if (! empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }

        $data = $tenant->data;
        $data['saml_idp'] = $config;
        $tenant->data = $data;

        return $tenant->save();
    }

    /**
     * Get the last authentication errors.
     */
    public function getLastErrors(): array
    {
        return $this->auth ? $this->auth->getErrors() : [];
    }

    /**
     * Get the last error reason.
     */
    public function getLastErrorReason(): ?string
    {
        return $this->auth ? $this->auth->getLastErrorReason() : null;
    }
}
