<div class="wrap">
    <h2>Telegram options</h2>

    <?php
        if(isset($_POST['telOp']) && !empty($_POST['telOp'])){
            update_option('telegram_bot_id',$_POST['telOp']['telegram_bot_id']);
            update_option('telegram_chat_id',$_POST['telOp']['telegram_chat_id']);
        }
        $botID  = get_option('telegram_bot_id');
        $chatID = get_option('telegram_chat_id');

        if(!isset($botID) || empty($botID)){
            update_option('telegram_bot_id','1');
        }
        if(!isset($chatID) || empty($chatID)){
            update_option('telegram_chat_id','1');
        }
    ?>

    <form method="post" action="">
        <?php wp_nonce_field('update-options'); ?>
        Bot Token
        <input type="text" size="60" name="telOp[telegram_bot_id]" value="<?php echo isset($botID) ? $botID : '' ?>" />
        Chat ID
        <input type="text" name="telOp[telegram_chat_id]" value="<?php echo isset($chatID) ? $chatID : ''?>" />
        <input type="hidden" name="action" value="update" />
        <input type="hidden" name="page_options" value="telegram_bot_id,telegram_chat_id" />

        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
        </p>
    </form>
</div>
