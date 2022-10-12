Hooks
=======

#### new_material_created

```php
    do_action('new_material_created', WP_POST $post, WP_USER $user, string $bundesland);
```

Beispiel:
```php
    add_action('new_material_created', function($post, $user, $bundesland){
    
        $msg = '<strong>%s aus % </strong><br> het ein neues Material unter dem Titel <a href="%s">%s</a> begonnen.'
        $msg = sprintf($msg, $user->display_name,  $bundesland, get_permalink($post->ID), $post->post_title )
        $matrix = new Matrix();
        $matrix->send('!yxzrommid:rpi-virtuell.de', $msg);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail( 'relimentarxyz@mailinator.com', '[relimentar] Beitragsentwurf', $msg, $headers );
    
    });
```

