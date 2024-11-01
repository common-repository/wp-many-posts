<?php
function ManyPosts_admin_page_callback(){ ?>
    <div class="wrap">
    <h2>ManyPosts Settings</h2>
    <form action="options.php" method="post"><?php
    settings_fields( 'ManyPosts_settings' );
    do_settings_sections( __FILE__ );

    //get the older values, wont work the first time
    $options = get_option( 'ManyPosts_settings' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row">Email</th>
                <td>
                    <fieldset>
                        <label>
                            <input name="ManyPosts_settings[ManyPosts_email]" type="text" id="ManyPosts_email" value="<?php echo (isset($options['ManyPosts_email']) && $options['ManyPosts_email'] != '') ? $options['ManyPosts_email'] : ''; ?>"/>
                            <br />
                            <span class="description">Please enter a valid email.</span>
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>
        <input type="submit" value="Save" />
    </form>
</div>
<?php }
?>