<!DOCTYPE html>
<html>
<head>
    <title>Welcome to {{ config('app.name') }}</title>
</head>
<body>
    <h1>Welcome to {{ config('app.name') }}</h1>
    
    <p>Hello {{ $user->name }},</p>
    
    <p>An administrator has created an account for you. Below are your login credentials:</p>
    
    <p>
        <strong>Email:</strong> {{ $user->email }}<br>
        <strong>Password:</strong> {{ $password }}
    </p>
    
    <p>For security reasons, we recommend changing your password after your first login.</p>
    
    <p>
        You can login at: <a href="{{ config('app.url') }}/login">{{ config('app.url') }}/login</a>
    </p>
    
    <p>If you have any questions, please contact the administrator.</p>
    
    <p>Best regards,<br>
    {{ config('app.name') }} Team</p>
</body>
</html> 