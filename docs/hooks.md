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
        $matrix = new rpiMatrixBridge();
        $matrix->send('!yxzrommid:rpi-virtuell.de', $msg);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail( 'relimentarxyz@mailinator.com', '[relimentar] Beitragsentwurf', $msg, $headers );
    
    });
```

```php
    do_action('new_material_published', WP_POST $post, WP_USER $user, string $bundesland);
```

Beispiel:
```php
    add_action('new_material_published', function($post, $user, $bundesland){
    
        $msg = '<strong>Redaktion erforderlich:</strong><br>Sas Material unter dem Titel <a href="%s">%s</a> wurde von %s aus %  wurde veröffentlicht.'
        $msg = sprintf(get_permalink($post->ID), $post->post_title, $msg, $user->display_name,  $bundesland)
        
        $matrix = new rpiMatrixBridge();
        $matrix->send('!yxzrommid:rpi-virtuell.de', $msg);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail( 'relimentarxyz@mailinator.com', '[relimentar] Veröffentlichung', $msg, $headers );
    
    });
```
