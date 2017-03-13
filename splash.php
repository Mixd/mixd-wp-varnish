<?php if ( current_user_can( 'mixd_wp_plugins' ) ) : ?>
    <?php $plugins = get_plugins(); ?>
    <section id="colophon" class="wrap">
        <br>
        <img src="http://www.mixd.co.uk/content/themes/mixd/assets/img/mixd-logo-black.svg" alt="">
        
        <h1>Mixd WordPress Directory</h1>
        <p>For help and support with using plugins developed by <a href="http://www.mixd.co.uk">Mixd</a> please email
            <a href="mailto:support@mixd.co.uk">support@mixd.co.uk</a>.</p>
        <p>For emergency support please call us on <a href="tel:01423598008">01423 598008</a>.</p>
        
        <?php if ( $plugins ) : ?>
            <table class="wp-list-table striped widefat">
                <thead>
                <tr>
                    <th scope="col">Plugin Name</th>
                    <th scope="col">Version</th>
                    <th scope="col">Description</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ( $plugins as $plugin ) : ?>
                    <?php
                    $name    = $plugin['Name'];
                    $version = $plugin['Version'];
                    $desc    = $plugin['Description'];
                    $dev_url = $plugin['PluginURI'];
                    if ( strpos( $name, "Mixd Plugins:" ) === false ) {
                        continue;
                    }
                    ?>
                    <tr>
                        <td>
                            <?php echo $name; ?>
                        </td>
                        <td>
                            <a title="Check for latest version" href="<?php echo untrailingslashit( $dev_url ); ?>/releases/latest"><?php echo $version; ?></a>
                        </td>
                        <td>
                            <?php echo $desc; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="3">
                        <small>Copyright Mixd <?php echo date( 'Y' ); ?> &copy;</small>
                    </td>
                </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </section>
<?php endif; ?>
