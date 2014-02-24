<?php
function show_header()
{
	global $ws_title, $TLD;
	?>
	<!DOCTYPE html>
<html lang="en-gb" dir="ltr">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo $ws_title; ?></title>
        <link rel="shortcut icon" href="/images/32.png" type="image/x-icon">
        <link rel="apple-touch-icon-precomposed" href="/images/128.png">
        <link id="data-uikit-theme" rel="stylesheet" href="/css/uikit.css">
        <script src="/js/jquery.js"></script>
        <script src="/js/uikit.js"></script>
    </head>

    <body class="tm-background">

        <nav class="tm-navbar uk-navbar uk-navbar-attached">
            <div class="uk-container uk-container-center">

                <a class="uk-navbar-brand uk-hidden-small" href="/"><img class="uk-margin uk-margin-remove" src="/images/128.png" width="90" height="30" title="<?php echo $ws_title; ?>" alt="<?php echo $ws_title; ?>"></a>

                <ul class="uk-navbar-nav uk-hidden-small">
                <?php
                	if(!isset($_SESSION['username']))
						{
							echo '<li><a href="user.php?action=frm_login">Login</a></li>';
							echo '<li><a href="user.php?action=frm_register">Register</a></li>';
						} else {
							echo '<li><a href="user.php?action=view_account">Manage Account</a></li>';
							echo '<li><a href="user.php?action=logout">Logout</a></li>';
						}
				?>
                </ul>

                <a href="#tm-offcanvas" class="uk-navbar-toggle uk-visible-small" data-uk-offcanvas></a>

                <div class="uk-navbar-brand uk-navbar-center uk-visible-small"><img src="/images/64.png"  height="30" title="UIkit" alt="UIkit"></div>

            </div>
        </nav>

<?php
}

function show_footer()
{
	?>
	  <div class="tm-footer">
            <div class="uk-container uk-container-center uk-text-center">

                <ul class="uk-subnav uk-subnav-line">
                    <li><a href="https://opennicproject.org">OpenNIC</a></li>
                    <li><a href="https://twitter.com/dotpirateTLD">Twitter</a></li>
                    <li><a href="https://github.com/teamcoltra">GitHub</a></li>
                    <li><a href="https://Facebook.com/dotpirateTLD">Facebook</a></li>
                </ul>

                <div class="uk-panel">
                    <p>No copyright on content, logo and name can be used as long as it doesn't create confusion for users or imply endorsement of a product or service.</p>
                    <a href="index.html"><img src="/images/64.png" title="UIkit" alt="UIkit"></a>
                </div>

            </div>
        </div>
         <div id="tm-offcanvas" class="uk-offcanvas">

            <div class="uk-offcanvas-bar">

                <ul class="uk-nav uk-nav-offcanvas uk-nav-parent-icon" data-uk-nav="{ multiple: true }">
				<?php
                if(!isset($_SESSION['username']))
	{
		?>
		<li class="uk-nav-header"><a href="user.php?action=frm_login">Login</a></li>
		<li class="uk-nav-header"><a href="user.php?action=frm_register">Register</a></li>
		<?php
	} else {
		?>
                    <li class="uk-parent"><a href="#">Account Options</a>
                        <ul class="uk-nav-sub">
                        	<li><a href="user.php?action=view_account">Manage Account</a></li>
							<li><a href="user.php?action=logout">Logout</a></li>
                        </ul>
                    </li>
                </ul>
<?php } /* end logged in menu */?>
            </div>
            </div>
            </body>
            </html>
<?php } /* end footer function*/?>