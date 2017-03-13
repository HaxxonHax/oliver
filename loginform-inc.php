					<h1>Secure FTP Portal</h1>
					<h2>Welcome to the FTP Server Portal.</h2>
					<div class="innerContent">
						<p>All information, including personal information, placed or sent over this system may be monitored. Anyone using this system expressly consents to monitoring.</p><br/>
					</div><br/>
<?php 
  if (isset($errmsg)) { 
    echo("<div style='color:red;margin-left:auto;margin-right:auto;font-size:150%;'><center>$errmsg</center></div>");
  }
?>
					<div class="login">
						<form action="index.php" method="post">
							<p class="label">Username:</p><input class="field" type="text" name="user">
							<p class="label">Password:</p><input class="field" type="password" name="passwd">
							<p class="label">Language:</p><select class="field" name="setlanguage">
								<option value="bg-bg">Bulgarian</option>
								<option value="cz-cz">Czech</option>
								<option value="nl-nl">Dutch</option>
								<option value="en-uk" selected>English</option>
								<option value="fr-fr">French</option>
								<option value="de-de">German</option>
								<option value="ja-jp">Japanese</option>
								<option value="pt-br">Portuguese (Brazil)</option>
								<option value="es-es">Spanish</option>
								<option value="tr-tr">Turkish</option>
							</select>
							<a href="#"><input id="button" class="field" type="submit" value="Login"></a>
						</form>
					</div>
