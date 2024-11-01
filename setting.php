<?php

defined('ABSPATH') or die('This page may not be accessed directly.');

if (!VilfIoUtils::can_modify_plugin_settings()) {
    // user does not have permission to edit plugin setting
    die('Nemáte dodatečné oprávnění k úpravě natavení pluginu.');
}

$vilfio_wp_settings = VilfIo::loadSetting();
?>


<div class="wrap">
    <h1>Nastavení Vilf.io push notifikace</h1>

    <form method="post" action="#" novalidate="novalidate">
        <?php
        // Add an nonce field so we can check for it later.
        wp_nonce_field(VilfIoAdmin::$SAVE_CONFIG_NONCE_ACTION, VilfIoAdmin::$SAVE_CONFIG_NONCE_KEY, true);
        ?>
        <h2>Základní nastavení</h2>
        <table class="form-table" role="presentation">
            <tbody>
            <tr>
                <th scope="row">
                    <label for="app_id">ID Webu</label>
                </th>
                <td>
                    <input name="app_id" type="text" id="app_id"
                           value="<?php echo esc_attr($vilfio_wp_settings['app_id']); ?>" class="regular-text">
                    <p class="description">ID webu naleznete po přihlášení do Vilf.io vedle odkazu do administrace.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="app_id">API token</label>
                </th>
                <td>
                    <input name="api_token" type="text" id="api_token"
                           value="<?php echo esc_attr($vilfio_wp_settings['api_token']); ?>" class="regular-text">
                    <p class="description">API token vygenerujete v administraci Vilf.io v detailu aplikace – na stránce „Nastavení webu" → „Nastavení API“</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="app_id">Url ikony odeslaných notifikací</label>
                </th>
                <td>
                    <input name="icon_url" type="text" id="icon_url"
                           value="<?php echo esc_attr($vilfio_wp_settings['icon_url']); ?>" class="regular-text">
                </td>
            </tr>
            </tbody>
        </table>
        
        <h2>Povolení ukládání Vilf.io tagů</h2>
        <p class="">Vilf.io tagy slouží k personalizaci push zpráv, segmentaci uživatelů a k automatizaci notifikací.</p>

        <table class="form-table" role="presentation">
            <tbody>
            <tr class="option-site-visibility">
                <th scope="row">Jméno uživatele</th>
                <td>
                    <label for="tags_name">
                        <input name="tags_name" type="checkbox" id="tags_name" <?php echo $vilfio_wp_settings['tags_name']?'checked="checked"':''?>>
                        Umožní v notifikacích oslovovat uživatele křestním jménem.
                    </label>
                </td>
            </tr>
            <tr class="option-site-visibility">
                <th scope="row">Obsah košíku (WooCommerce)</th>
                <td>
                    <label for="tags_cart">
                        <input name="tags_cart" type="checkbox" id="tags_cart" <?php echo $vilfio_wp_settings['tags_cart']?'checked="checked"':''?>>
                        Umožní odesílat automatické notifikace pro záchranu zapomenutého košíku.
                    </label>
                </td>
            </tr>
            <tr class="option-site-visibility">
                <th scope="row">Sledování objednávky (WooCommerce)</th>
                <td>
                    <label for="tags_order">
                        <input name="tags_order" type="checkbox" id="tags_order" <?php echo $vilfio_wp_settings['tags_order']?'checked="checked"':''?>>
                        Umožní při změně statusu objednávky odesílat automatické notifikace.
                    </label>
                </td>
            </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Uložit změny">
        </p>
    </form>
</div>
