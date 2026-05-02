<?php

declare(strict_types=1);

return [
    'quota_warning' => '{app_name}: Your {quota_type} usage is at {percentage}%. Upgrade to continue: {upgrade_url}',
    'trial_expiry' => '{app_name}: Your trial ends in {days} day(s). Upgrade now and save {discount}%: {upgrade_url}',
    'payment_failed' => '{app_name}: Payment failed for {amount}. Update payment method: {billing_url}',
    'subscription_cancelled' => '{app_name}: Your subscription has been cancelled. You have access until {end_date}.',
    'subscription_renewed' => '{app_name}: Subscription renewed successfully. Next billing: {next_billing_date}.',
    'subscription_upgraded' => '{app_name}: You\'ve upgraded to {plan_name}! Enjoy your new features.',
    'welcome' => 'Welcome to {app_name}! We\'re excited to have you. Get started: {dashboard_url}',
    'verification_code' => '{app_name}: Your verification code is {code}. Valid for {validity} minutes.',
    'password_reset' => '{app_name}: Reset your password: {reset_url}. Link expires in {validity} minutes.',
    'account_suspended' => '{app_name}: Your account has been suspended. Contact support: {support_url}',
    'account_reactivated' => '{app_name}: Welcome back! Your account has been reactivated. Login now: {app_url}',
    'invoice_paid' => '{app_name}: Payment received! Invoice #{invoice_number} paid: {amount}. Thank you!',
    'low_credit' => '{app_name}: Low credit warning. Balance: {balance}. Add funds: {billing_url}',
    'feature_enabled' => '{app_name}: {feature_name} is now enabled on your account. Try it now!',
    'team_invitation' => '{app_name}: You\'ve been invited to join {team_name}. Accept invite: {invite_url}',
];
