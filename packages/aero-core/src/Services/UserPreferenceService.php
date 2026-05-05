<?php

namespace Aero\Core\Services;

use Aero\Core\Models\UserPreference;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserPreferenceService
{
    /**
     * Get a preference value for a user with caching.
     */
    public function get($userId, $key, $default = null)
    {
        $cacheKey = "user_preferences:{$userId}:{$key}";
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($userId, $key, $default) {
            $value = UserPreference::getPreference($userId, $key);
            
            // If no user preference, try default from config
            if ($value === null) {
                $value = $this->getDefaultPreference($key, $default);
            }
            
            return $value;
        });
    }

    /**
     * Set a preference value for a user.
     */
    public function set($userId, $key, $value, $metadata = null)
    {
        // Validate the preference value
        if (!$this->validatePreference($key, $value)) {
            Log::warning("Invalid preference value for key: {$key}", ['user_id' => $userId, 'value' => $value]);
            return false;
        }

        $preference = UserPreference::setPreference($userId, $key, $value, $metadata);
        
        // Clear cache for this preference
        $this->clearCache($userId, $key);
        
        Log::info("User preference updated", ['user_id' => $userId, 'key' => $key]);
        
        return $preference;
    }

    /**
     * Delete a preference for a user.
     */
    public function delete($userId, $key)
    {
        $result = UserPreference::deletePreference($userId, $key);
        
        // Clear cache for this preference
        $this->clearCache($userId, $key);
        
        return $result;
    }

    /**
     * Check if a user has a specific preference.
     */
    public function has($userId, $key)
    {
        return UserPreference::hasPreference($userId, $key);
    }

    /**
     * Get all preferences for a user.
     */
    public function getAll($userId)
    {
        $cacheKey = "user_preferences:{$userId}:all";
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($userId) {
            $userPreferences = UserPreference::getAllPreferences($userId);
            $defaultPreferences = $this->getDefaultPreferences();
            
            // Merge with defaults, user preferences take precedence
            return array_merge($defaultPreferences, $userPreferences);
        });
    }

    /**
     * Bulk set preferences for a user.
     */
    public function bulkSet($userId, array $preferences)
    {
        $validated = [];
        
        foreach ($preferences as $key => $value) {
            if ($this->validatePreference($key, $value)) {
                $validated[$key] = $value;
            }
        }
        
        if (!empty($validated)) {
            UserPreference::bulkSetPreferences($userId, $validated);
            
            // Clear all caches for this user
            $this->clearAllCache($userId);
            
            Log::info("Bulk user preferences updated", ['user_id' => $userId, 'count' => count($validated)]);
        }
        
        return $validated;
    }

    /**
     * Get default preference value from config.
     */
    protected function getDefaultPreference($key, $default = null)
    {
        $defaults = $this->getDefaultPreferences();
        return $defaults[$key] ?? $default;
    }

    /**
     * Get all default preferences from config.
     */
    protected function getDefaultPreferences()
    {
        return config('user-preferences.defaults', []);
    }

    /**
     * Validate a preference value based on key.
     */
    protected function validatePreference($key, $value)
    {
        $validations = config('user-preferences.validations', []);
        
        if (isset($validations[$key])) {
            $validation = $validations[$key];
            
            // Type validation
            if (isset($validation['type'])) {
                switch ($validation['type']) {
                    case 'boolean':
                        return is_bool($value) || in_array($value, ['true', 'false', '0', '1', 0, 1], true);
                    case 'string':
                        return is_string($value);
                    case 'integer':
                        return is_int($value);
                    case 'array':
                        return is_array($value);
                }
            }
            
            // Allowed values validation
            if (isset($validation['allowed']) && is_array($validation['allowed'])) {
                return in_array($value, $validation['allowed'], true);
            }
        }
        
        return true;
    }

    /**
     * Clear cache for a specific preference.
     */
    protected function clearCache($userId, $key)
    {
        Cache::forget("user_preferences:{$userId}:{$key}");
        Cache::forget("user_preferences:{$userId}:all");
    }

    /**
     * Clear all caches for a user.
     */
    protected function clearAllCache($userId)
    {
        // Clear all preference caches for this user
        // In production, you might want to use a cache tag
        Cache::forget("user_preferences:{$userId}:all");
    }

    /**
     * Get notification preferences for a user.
     */
    public function getNotificationPreferences($userId)
    {
        $defaults = [
            'email_enabled' => true,
            'in_app_enabled' => true,
            'push_enabled' => false,
            'digest_frequency' => 'immediate', // immediate, daily, weekly
            'dnd_enabled' => false,
            'dnd_start_time' => null,
            'dnd_end_time' => null,
        ];

        $userPrefs = [];
        foreach ($defaults as $key => $default) {
            $prefKey = "notifications.{$key}";
            $userPrefs[$key] = $this->get($userId, $prefKey, $default);
        }

        return $userPrefs;
    }

    /**
     * Set notification preferences for a user.
     */
    public function setNotificationPreferences($userId, array $preferences)
    {
        $mapped = [];
        foreach ($preferences as $key => $value) {
            $mapped["notifications.{$key}"] = $value;
        }

        return $this->bulkSet($userId, $mapped);
    }

    /**
     * Get theme preferences for a user.
     */
    public function getThemePreferences($userId)
    {
        $defaults = [
            'theme' => 'system', // light, dark, system
            'accent_color' => 'blue',
            'density' => 'comfortable', // comfortable, compact
            'border_radius' => 'medium', // none, small, medium, large
        ];

        $userPrefs = [];
        foreach ($defaults as $key => $default) {
            $prefKey = "theme.{$key}";
            $userPrefs[$key] = $this->get($userId, $prefKey, $default);
        }

        return $userPrefs;
    }

    /**
     * Set theme preferences for a user.
     */
    public function setThemePreferences($userId, array $preferences)
    {
        $mapped = [];
        foreach ($preferences as $key => $value) {
            $mapped["theme.{$key}"] = $value;
        }

        return $this->bulkSet($userId, $mapped);
    }

    /**
     * Get locale preferences for a user.
     */
    public function getLocalePreferences($userId)
    {
        $defaults = [
            'language' => config('app.locale', 'en'),
            'timezone' => config('app.timezone', 'UTC'),
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'currency' => 'USD',
            'number_format' => '1,234.56',
        ];

        $userPrefs = [];
        foreach ($defaults as $key => $default) {
            $prefKey = "locale.{$key}";
            $userPrefs[$key] = $this->get($userId, $prefKey, $default);
        }

        return $userPrefs;
    }

    /**
     * Set locale preferences for a user.
     */
    public function setLocalePreferences($userId, array $preferences)
    {
        $mapped = [];
        foreach ($preferences as $key => $value) {
            $mapped["locale.{$key}"] = $value;
        }

        return $this->bulkSet($userId, $mapped);
    }

    /**
     * Get accessibility preferences for a user.
     */
    public function getAccessibilityPreferences($userId)
    {
        $defaults = [
            'font_size' => 'medium', // small, medium, large, extra-large
            'high_contrast' => false,
            'reduced_motion' => false,
            'screen_reader' => false,
        ];

        $userPrefs = [];
        foreach ($defaults as $key => $default) {
            $prefKey = "accessibility.{$key}";
            $userPrefs[$key] = $this->get($userId, $prefKey, $default);
        }

        return $userPrefs;
    }

    /**
     * Set accessibility preferences for a user.
     */
    public function setAccessibilityPreferences($userId, array $preferences)
    {
        $mapped = [];
        foreach ($preferences as $key => $value) {
            $mapped["accessibility.{$key}"] = $value;
        }

        return $this->bulkSet($userId, $mapped);
    }
}
