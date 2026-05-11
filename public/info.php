<?php
// Diagnostic page - DELETE after confirming the app works
echo '<h2>PHP OK - version ' . phpversion() . '</h2>';
echo '<p>BASE PATH: ' . htmlspecialchars(dirname($_SERVER['SCRIPT_NAME'])) . '</p>';
echo '<p>mod_rewrite: ' . (in_array('mod_rewrite', apache_get_modules()) ? '<b style="color:green">ENABLED</b>' : '<b style="color:red">NOT LOADED</b>') . '</p>';
echo '<p>mod_headers: ' . (in_array('mod_headers', apache_get_modules()) ? '<b style="color:green">ENABLED</b>' : '<b style="color:orange">Not loaded (ok, optional)</b>') . '</p>';
echo '<p>Sessions writable: ' . (is_writable(session_save_path() ?: sys_get_temp_dir()) ? '<b style="color:green">YES</b>' : '<b style="color:red">NO</b>') . '</p>';
echo '<p>ROOT writeable (logs): ' . (is_writable(dirname(__DIR__) . '/logs') ? '<b style="color:green">YES</b>' : '<b style="color:red">NO - run: chmod 777 ' . dirname(__DIR__) . '/logs</b>') . '</p>';
echo '<p>Storage writable: ' . (is_writable(dirname(__DIR__) . '/storage') ? '<b style="color:green">YES</b>' : '<b style="color:red">NO - run: chmod 777 ' . dirname(__DIR__) . '/storage</b>') . '</p>';
