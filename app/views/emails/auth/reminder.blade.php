<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		<div>
			Чтобы обновить пароль перейдите по <a href="{{ URL::to('password/reset', array($token)) }}">ссылке</a>.
		</div>
	</body>
</html>