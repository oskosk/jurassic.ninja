# SpecialOps Page

Similarly to the `/create` page, a `/specialops` page can be created which gives control over site configuration.

Recommended `/specialops` page content:
```
<!-- wp:paragraph {"className":"has-border","textColor":"primary","fontSize":"small"} -->
<p class="has-border has-primary-color has-text-color has-small-font-size">IMPORTANT: Jurassic Ninja is meant to be used as an internal tool only. It’s not branded (there’s neither an intention to) and is not set up to scale to mass public use. For more questions ask in #jurassic-ninja Slack.</p>
<!-- /wp:paragraph -->

<!-- wp:group -->
<div class="wp-block-group"><div class="wp-block-group__inner-container"><!-- wp:html -->
<p><a class="btn btn-default" href="#" data-feature="subdir_multisite" data-is-create-button="">Launch Multisite on subdirs</a><br><a class="btn btn-default" href="#" data-feature="subdomain_multisite" data-is-create-button="">Launch Multisite on subdomains</a><br><a class="btn btn-default" href="#" data-is-create-button="">Launch single site</a></p>

<figure><img id="img1" style="display: none;" src="https://media.giphy.com/media/uIRyMKFfmoHyo/giphy.gif" data-failure-img-src="https://i.imgur.com/vdyaxmx.gif"></figure><figure><img id="img2" style="display: none;" src="https://i1.wp.com/media.giphy.com/media/KF3r4Q6YCtfOM/giphy.gif?ssl=1"></figure><p style="text-align: center;"><br></p>
<p id="progress" class="lead" style="display: none; text-align: center;" data-success-message="The new WP is ready to go, visit it!" data-error-message="Oh No! There was a problem launching the new WP.">Launching a fresh WP with a Jetpack ...</p>
<!-- /wp:html --></div></div>
<!-- /wp:group -->

<!-- wp:html -->
<ul style="list-style: none; padding-left: 0; display: flex; flex-wrap: wrap;">
<li style="min-width: 30%;">
<div class="checkbox"><label><input checked="checked" type="checkbox" data-feature="wp-debug-log">&nbsp;WP_DEBUG and WP_DEBUG_LOG</label></div>
</li>
<li style="min-width: 30%;">
<div class="checkbox"><label><input type="checkbox" data-feature="wordpress-4">&nbsp;Launch with latest WordPress 4</label></div>
</li>
<li style="min-width: 30%;">
<div class="checkbox"><label><input type="checkbox" data-feature="content">&nbsp;Add pregenerated content to the site</label></div>
</li>
<li style="min-width: 30%;">
<div class="form-group"><label for="php_version">&nbsp;PHP version:</label><br><select class="form-control" name="php_version" data-feature="php_version">
<option value="php5.4">PHP 5.4</option>
<option value="php5.6">PHP 5.6</option>
<option value="php7.0">PHP 7.0</option>
<option value="php7.2">PHP 7.2</option>
<option value="php7.3">PHP 7.3</option>
<option selected="selected" value="php7.4">PHP 7.4</option>
<option value="php8.0">PHP 8.0</option>
</select></div>
</li>
<li style="min-width: 30%;">
<div class="form-group"><label for="language">&nbsp;Language:</label><br><select id="language" class="form-control" name="language" data-feature="language">
<option selected="selected" value="">English (United States)</option>
</select></div>
</li>
</ul>
<!-- /wp:html -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":4} -->
<h4>Jetpack</h4>
<!-- /wp:heading -->

<!-- wp:html -->
<ul style="list-style: none; padding-left: 0; ">
<li>
<div class="checkbox"><label><input checked="checked" type="checkbox" data-feature="jetpack">&nbsp;Include Jetpack</label>
[jn_jetpack_products_list]
</li>
<li>
<div class="checkbox"><label><input type="checkbox" data-feature="jetpack-beta">&nbsp;Include Jetpack Beta</label></div>
</li>
<li>
<div class="form-group" id="jetpack_beta_branches_group"></div>
</li>
<li>
<div class="checkbox"><label><input type="checkbox" data-feature="vaultpress">&nbsp;Include VaultPress</label></div>
</li>
<li>
<div class="checkbox"><label><input type="checkbox" data-feature="zero-bs-crm">&nbsp;Include Jetpack CRM</label></div>
</li>
<li>
<div class="checkbox"><label><input type="checkbox" data-feature="jetpack-debug-helper">&nbsp;Include Jetpack Debug Helper</label></div>
</li>
<li>
<div class="checkbox"><label><input type="checkbox" data-feature="client-example">&nbsp;Include Client Example</label></div>
</li>
</ul>
<!-- /wp:html --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":4} -->
<h4>Woo</h4>
<!-- /wp:heading -->

<!-- wp:html -->
<ul style="list-style: none; padding-left: 0; ">
<li>
<div class="checkbox"><label><input type="checkbox" data-feature="woocommerce">&nbsp;Include WooCommerce</label></div>
</li>
<li>
<div class="checkbox"><label><input type="checkbox" data-feature="woocommerce-beta-tester">&nbsp;Include WooCommerce Beta Tester</label></div>
</li>
<li>
<div class="checkbox"><label><input type="checkbox" data-feature="wc-smooth-generator">&nbsp;Include WooCommerce Smooth Generator</label></div>
</li>
</ul>
<!-- /wp:html --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:group {"align":"wide"} -->
<div class="wp-block-group alignwide"><div class="wp-block-group__inner-container"><!-- wp:heading {"level":4} -->
<h4>Plugins</h4>
<!-- /wp:heading -->

<!-- wp:html -->
<ul style="list-style: none; padding-left: 0; display: flex; flex-wrap: wrap;">
<li style="min-width: 30%;">
<div class="checkbox"><label><input type="checkbox" data-feature="classic-editor">&nbsp;Include Classic Editor</label></div>
</li>
<li style="min-width: 30%;">
<div class="checkbox"><label><input type="checkbox" data-feature="gutenberg">&nbsp;Include Gutenberg plugin</label></div>
</li>

<li style="min-width: 30%;">
<div class="checkbox"><label><input type="checkbox" data-feature="code-snippets">&nbsp;Include Code Snippets</label></div>
</li>
<li style="min-width: 30%;">
<div class="checkbox"><label><input type="checkbox" data-feature="wordpress-beta-tester">&nbsp;Include WordPress Beta Tester</label></div>
</li>
<li style="min-width: 30%;">
<div class="checkbox"><label><input type="checkbox" data-feature="wp-downgrade">&nbsp;Include WP Downgrade</label></div>
</li>
<li style="min-width: 30%;">
<div class="checkbox"><label><input type="checkbox" data-feature="wp-rollback">&nbsp;Include WP Rollback</label></div>
</li>
<li style="min-width: 30%;">
<div class="checkbox"><label><input type="checkbox" data-feature="amp">&nbsp;Include AMP</label></div>
</li>
<li style="min-width: 30%;">
<div class="checkbox"><label><input type="checkbox" data-feature="config-constants">&nbsp;Include Config Constants plugin</label></div>
</li>
<li style="min-width: 30%;">
<div class="checkbox"><label><input type="checkbox" data-feature="wp-log-viewer">&nbsp;Include WP Log Viewer plugin</label></div>
</li>
</ul>
<!-- /wp:html --></div></div>
<!-- /wp:group -->

<!-- wp:paragraph {"align":"center","backgroundColor":"accent"} -->
<p class="has-text-align-center has-accent-background-color has-background">Sites are destroyed 7 days after the last time you signed in on that site.</p>
<!-- /wp:paragraph -->
```
