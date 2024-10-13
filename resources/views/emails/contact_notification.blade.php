<!DOCTYPE html>
<html>
<head>
    <title>COVID-19 Contact Notification</title>
</head>
<body>
<h1>COVID-19 Contact Notification</h1>

<p>Dear {{ $contactedUser->name }},</p>

<p>We regret to inform you that you were in contact with a person who has tested positive for COVID-19. Please take necessary precautions and monitor your symptoms.</p>

<p>Contact details:</p>
<ul>
    <li><strong>Infected Person:</strong> {{ $infectedUser->name }}</li>
    <li><strong>Location:</strong> {{ $sharedLocation->name }}</li>
    <li><strong>Date and Time of Contact:</strong> {{ $sharedCheckinTime }}</li>
</ul>

<p>If you experience any symptoms or wish to seek medical advice, please contact your healthcare provider.</p>

<p>Stay safe,</p>
<p>The {{ config('app.name') }} Team</p>
</body>
</html>

