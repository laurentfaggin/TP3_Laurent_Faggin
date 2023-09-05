<html>
	<title>Tp3 serveur2</title>
	<h1>tp3 serveur2 page2</h1>
	<?php 
	$host = 'mariadb';
	$user = 'root';
	$pass = 'rootpassword';
	$conn = new mysqli($host, $user, $pass);

	if ($conn->connect_error) {
		die("La connexion a échoué: " . $conn->connect_error);
	} 
	echo "Connexion réussie à MariaDB!";
	?>
</html>
