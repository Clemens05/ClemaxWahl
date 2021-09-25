<?php

define('MAINPATH', file_get_contents('mainpath'));

// configuration
$url = MAINPATH . '/change.php'; 
$file = 'wahlprognose.json';

// check if form has been submitted
if (isset($_POST['text']))
{
    if (isset($_POST['password'])) {
        if ($_POST['password'] == 'esozb1n') {
            // save the text contents
            file_put_contents($file, $_POST['text']);

            // redirect to form again
            header(sprintf('Location: %s', $url));
            printf('<a href="%s">Moved</a>.', htmlspecialchars($url));
            exit();
        }
    }
}

// read the textfile
$text = file_get_contents($file);

?>

<!-- HTML form -->
<form action="" method="post">
<textarea name="text" rows="50" cols="75"><?php echo htmlspecialchars($text) ?></textarea>
<textarea name="password">Bitte gebe das Passwort ein</textarea>
<input type="submit" />
<input type="reset" />
</form>