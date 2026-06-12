<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1> Student Flow Laravel Basido </h1>
    <div style="Border: 1px solid #ccc; padding: 20px;">
        <h2>Student Registration Form</h2>
        <form-action="/register" method="POST">
            @csrf
            <input type="text" name="name" placeholder="Name">
            <input type="email" name="email" placeholder="Email">
            <input type="number" name="age" placeholder="Age">
            <button type="submit">Register</button>
        </form-action>
</div>
</body>
</html>