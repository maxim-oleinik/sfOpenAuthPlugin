<?php
/**
 * Фома авторизации
 *
 * @param bool $error - Ошибка dicovery
 */
use_helper('JavascriptBase');
use_javascript('/sfOpenAuthPlugin/js/jquery.openid-selector.js');
use_stylesheet('/sfOpenAuthPlugin/css/openid-selector.css');


// Init form
echo javascript_tag("$(function(){  openid.init('openid_identifier');  });");
?>

<h2>Вход/Регистрация</h2>

<form action="<?php echo url_for('open_auth_login'); ?>" method="post" id="openid_form">
<div>
    <input type="hidden" name="action" value="verify" />
    <fieldset>
        <div id="openid_choice">
            <h3>Войти как пользователь:</h3>
            <div id="openid_btns"></div>
        </div>

        <div id="openid_input_area">
        <h3>Укажите свой OpenID</h3>
            <p>Или включите JavaScript, чтобы сделать это по-человечески.</p>
            <input id="openid_identifier" name="openid_identifier" type="text" value="http://" />
            <input id="openid_submit" type="submit" value="Вход"/>
        </div>

        <?php if (!empty($error)): ?>
            <div id="openid_error" class="error_list"><?php echo $error; ?></div>
        <?php endif; ?>
    </fieldset>
</div>
</form>
