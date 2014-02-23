<?php
/* Sample index page. Do what you want with it. Public domain. */
include("conf.php");
show_header();
?>
<div class="tm-section tm-section-color-white">
            <div class="uk-container uk-container-center uk-text-center">

                <img class="tm-logo" src="images/128.png" title="UIkit" alt="UIkit">
                <h1 class="uk-heading-large" style="margin-top:-20px;">.pirate</h1 >

                <p class="uk-text-large">A free TLD for freedom and sharing<br class="uk-hidden-small"> because when doing what's right is wrong, we are all pirates.</p>

                <a class="uk-button tm-button-download">Get Your .Pirate Domain</a>

            </div>
        </div>

        <div class="tm-section tm-section-color-2 tm-section-colored">
            <div class="uk-container uk-container-center uk-text-center">

                <h1 class="uk-heading-large">Features</h1>

                <p class="uk-text-large">.Pirate domains do more than just give you a sexy TLD<br class="uk-hidden-small">they protect your freedom and your users ability to find you.</p>

                <div class="uk-grid" data-uk-grid-margin>

                    <div class="uk-width-medium-1-4">
                        <div class="uk-panel">
                            <h2 class="uk-margin-top-remove">Secure</h2>
                            <p>Don't let your domain get seized by some government. .Pirate domains are secure and decenteralized, you don't have to worry!</p>
                        </div>
                    </div>
                    <div class="uk-width-medium-1-4">
                        <div class="uk-panel">
                            <h2 class="uk-margin-top-remove">Simple</h2>
                            <p>We manage your DNS if you want, or you can use your own nameservers and do it remotely. It's all free.</p>
                        </div>
                    </div>
                    <div class="uk-width-medium-1-4">
                        <div class="uk-panel">
                            <h2 class="uk-margin-top-remove">Private</h2>
                            <p>While we have a whois database, we strip your name and email from it. People have to use our website and fill out a CAPCHA to send you an email (which is processed through our servers, so your address is private).</p>
                        </div>
                    </div>
                    <div class="uk-width-medium-1-4">
                        <div class="uk-panel">
                            <h2 class="uk-margin-top-remove">Awesome</h2>
                            <p>The .Pirate space is full of awesome people, by getting a .Pirate domain name you are showing your users and the rest of the world that you are a cool person. Men and women will be more attracted to you.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="tm-section tm-section-color-white">
            <div class="uk-container uk-container-center uk-text-center">

                <h1 class="uk-heading-large">Go Pro!<small>coming soon</small></h1>

                <p class="uk-text-large">We will be launching some added services to help you maintain your privacy and manage your site.</p>

                <div class="uk-grid uk-grid-divider" data-uk-grid-margin>

                    <div class="uk-width-medium-1-3">
                        <div class="uk-panel">
                            <h2>Reverse Proxy</h2>
                            <p>CloudFlare doesn't work for OpenNIC, so we are building our own system. It's not a CDN, but it will prevent your users from seeing your IP address.</p>
                        </div>
                    </div>
                    <div class="uk-width-medium-1-3">
                        <div class="uk-panel">
                            <h2>API Management</h2>
                            <p>Update your domain via an API and do cool things like dynamic dns and more.</p>
                        </div>
                    </div>
                    <div class="uk-width-medium-1-3">
                        <div class="uk-panel">
                            <h2>Tech Support</h2>
                            <p>All pro accounts will also have access to email questions and get priority support. Also they can come onto our IRC channel and get dedicated live support instead of our more traditional "reply when we have a chance".</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
       <?php
show_footer();
?>