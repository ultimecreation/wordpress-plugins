<?php
define('DIR_PATH', plugin_dir_path(__FILE__));
/**
 * Plugin Name: Wordpress-plugins
 * Author: Someone
 * Description: Création d'un plugin de base avec stackage en bdd
 * Version: v1.0.0
 */
// Plugin Name:... is ok BUT Plugin Name :... does not work

////////////////////////////
// ACTIVATION -DÉSACTIVATION
////////////////////////////
/**
 * à l'activation du plugins,on créer la table dans la bdd
 * documentation => https://developer.wordpress.org/plugins/plugin-basics/activation-deactivation-hooks/
 * 
 * do_some_stuffs_on_plugin_activation
 * @return void
 */
function do_some_stuffs_on_plugin_activation(){
    // chargement du fichier contenant les fonction maybe_create_table et dbDelta
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // get db connection
    global $wpdb;

    //set tablename and sql query 
    $tablename = "{$wpdb->prefix}email_list";
    $sql = "
        CREATE TABLE `$tablename`(
            id INT(11) PRIMARY KEY AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL
        )ENGINE=InnoDB;
    ";
    // run query
    maybe_create_table($tablename, $sql);
    
}
register_activation_hook( __FILE__, 'do_some_stuffs_on_plugin_activation' );

/**
 * documentation => https://developer.wordpress.org/plugins/plugin-basics/activation-deactivation-hooks/
 * do_some_stuffs_on_plugin_deactivate
 *
 * @return void
 */
function do_some_stuffs_on_plugin_deactivate(){

    // on nettoie un peu le cache
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'do_some_stuffs_on_plugin_deactivate' );

////////////////////////////
// FIN ACTIVATION -DÉSACTIVATION
////////////////////////////




////////////////////////////
// FRONTEND SHORTCODE
////////////////////////////
/**
 * documentation 
 * => https://www.php.net/manual/fr/function.ob-start.php
 * => https://developer.wordpress.org/reference/functions/do_shortcode/
 * fonction renvoyant le contenu html du shortcode
 * render_my_html_form_on_the_frontend
 *
 * @return void
 */
function render_my_html_form_on_the_frontend()
{
    ob_start();
    ?>
        <div id="my-form-feedback"></div>
        <form id="my-form">
            <div>
                <label for="name">Nom</label><br>
                <input type="text" name="name" id="name">
            </div>
            <div>
                <label for="email">Email</label><br>
                <input type="email" name="email" id="email">
            </div>
            <input type="hidden" name="my_submit_nonce" id="my_submit_nonce" value="<?php echo wp_create_nonce('my_submit_nonce');?>">
            <input type="hidden" name="action" id="action" value="my_submit_ajax_handler">
            <input type="hidden" name="url" id="url" value="<?php echo admin_url( 'admin-ajax.php' ) ;?>">
            <input type="submit" value="Soumettre" id="submitBtn">
        </form>
        <script src="<?php echo plugin_dir_url( __FILE__ ).'js/myjs.js';?>" defer></script>
    <?php
    ob_end_flush();
    
}
/**
 * création de liaison entre le nom du shortcode et son contenu
 * register_my_form_shortcode
 *
 * @return void
 */
function register_my_form_shortcode()
{
    add_shortcode( 'show_my_form', 'render_my_html_form_on_the_frontend' );
}
add_action( 'init', 'register_my_form_shortcode' );
////////////////////////////
// FIN FRONTEND SHORTCODE
////////////////////////////




////////////////////////////
// FRONTEND FROM SUBMISSION
////////////////////////////



/**
 * prise en charge de la soumission du formulaire
 * documentation => https://codex.wordpress.org/AJAX_in_Plugins
 * 
 * do_my_stuffs_when_submit_button_is_clicked
 * @return void
 */
function do_my_stuffs_when_submit_button_is_clicked() 
{
    // vérifie que l'action soit celle attendue
    if($_POST['action'] === 'my_submit_ajax_handler')
    {
        // le nonce n'est pas vérifié on renvoie une erreur
        if(!wp_verify_nonce($_POST['my_submit_nonce'],'my_submit_nonce')){
            wp_send_json_error(array(
                'success'=>false,
                'message'=>"Une erreur inattendue est survenue"
            ));
        } 

        // le nonce est ok, on peut continuer
        // nettoyage des données recues
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);

        // on récupère la connexion à la bdd
        global $wpdb;

        // on verifie si l'email déjà en bdd
        $email_exists = $wpdb->query(
            $wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}email_list
                WHERE email=%s",
                array($email)
            )
        );

        // si l'email est déjà présent en bdd,on renvoie une erreur
        if($email_exists){
            wp_send_json_error(array(
                'success'=> false,
                'message'=>"L'email est déjà enregistré"
            ));
        }

        // si on arrive ici, c'est que l'email n'existe pas dans la bdd
        // on prèpare la requête d'insertion
        $wpdb->query(
            $wpdb->prepare("
                INSERT INTO {$wpdb->prefix}email_list
                SET name=%s, email=%s",
                array($name,$email))
        );

        // si les données on été enregistrées en bdd, on doit pouvoir récupérer le dernier ID
        if($wpdb->insert_id){
            wp_send_json(array(
                'success'=> true,
                'message'=> "Les données ont été enregistrées avec succès"
            ));
           
        } 
        // une erreur inattendue est survenue, on renvoie un message d'erreur
        else{
            wp_send_json_error(array(
                'success'=>false,
                'message'=>"Une erreur inattendue est survenue"
            ));
        }
         wp_die();
    }
}
add_action( 'wp_ajax_nopriv_my_submit_ajax_handler', 'do_my_stuffs_when_submit_button_is_clicked' );
add_action( 'wp_ajax_my_submit_ajax_handler', 'do_my_stuffs_when_submit_button_is_clicked' );
////////////////////////////
// FIN FRONTEND FROM SUBMISSION
////////////////////////////




////////////////////////////
// ADMINISTRATION
////////////////////////////
/**
 * Création de la page d'administration avec l'ajout d'un bouton dans le menu
 * documentation => https://developer.wordpress.org/reference/functions/add_menu_page/
 * 
 * create_my_custom_menu_page_and_menu_item
 * @return void
 */
function create_my_custom_menu_page_and_menu_item(){
    
    add_menu_page(
        __( 'Wp email list', 'my-textdomain' ),
        __( 'Wp email list', 'my-textdomain' ),
        'manage_options',
        'wp-email-list',
        'render_my_admin_page_content',
        'dashicons-schedule',
        3
    );
}
add_action( 'admin_menu', 'create_my_custom_menu_page_and_menu_item' );
 
/**
 * Afficher le contenu de la nouvelle page d'administration
 * les class=... sont des classes de wp
 */
function render_my_admin_page_content()
{
    // on affiche d'abord le formulaire de modification si action=edit_entry est défini dans l'url
    // sinon on affiche la liste 
    if($_GET['action'] =='edit_entry'){
        do_my_stuffs_when_entry_edit_link_is_clicked();
    }
    ?>
        <div class="wrap">
        <h1 class="wp-heading-inline">Liste </h1>
            <?php $status = get_transient('contact_deleted');
                if($status){
                    echo "<p style='background: green;padding:0.25rem;color:white;text-align:center;'>$status</p>";
                    delete_transient('contact_deleted');
                }
            ?>

            <hr class="wp-header-end">

            <table class="wp-list-table widefat fixed striped table-view-list posts">
                <thead>
                    <tr>
                        <th class="manage-column" >
                            <span>Nom</span>
                        </th>
                        <th class="manage-column" >
                            <span>Email</span>
                        </th>
                        <th class="manage-column" >
                            <span>Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        // recupère la connexion à la bdd
                        global $wpdb;

                        // recupère toutes les entrées en bdd avant de boucler dessus
                        $entries = $wpdb->get_results(
                            "SELECT * FROM {$wpdb->prefix}email_list"
                        );
                    ?>
                    <?php foreach($entries as $entry):?>
                        <tr>
                            <td><?php echo $entry->name; ?></td>
                                <td><?php echo $entry->email ?></td>
                            
                                <td>
                                    <a href="<?php echo admin_url("admin.php?page=wp-email-list&action=edit_entry&id={$entry->id}");?>" >Éditer</a>
                                    <form action="<?php echo admin_url('admin-post.php');?>" method="POST">
                                        <input type="hidden" name="action" value="delete_entry">
                                        <input type="hidden" name="idToDelete" value="<?php echo $entry->id;?>">
                                        <input type="hidden" name="delete_entry_nonce" value="<?php echo wp_create_nonce('delete_entry_nonce');?>">
                                        <?php
                                        submit_button('&#128465;', 'secondary', '', true, array('style' => 'background:red;color:white'));
                                        ?>
                                    </form>
                                </td>
                             </tr>    
                       <?php endforeach;?>
                </tbody>
            </table>
        </div>
    <?php
}

/**
 * on récupère une entrée en bdd via son id pour l'afficher sur le formulaire
 * 
 * do_my_stuffs_when_entry_edit_link_is_clicked
 * @return void
 */
function do_my_stuffs_when_entry_edit_link_is_clicked(){
    // récupère la connexion à la bdd
    global $wpdb;

    // récupère l'id dans l'url
    $id = intval($_GET['id']);

    // récupère la ligne en bdd avec une requête préparée
    $entry = $wpdb->get_row(
        $wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}email_list
            WHERE id=%d",
            array($id)    
        )
   );
    ?>
    <h1 class="wp-heading-inline">Modification</h1>
    <a href="<?php echo admin_url('admin.php?page=wp-email-list');?>" class="button action" >&lt; Retour</a>
    <br><br>
    <form action="<?php echo admin_url('admin-post.php');?>" method="POST">
        <div>
            <label for="name">Nom</label>
            <input type="text" name="name" id="name" value="<?php echo $entry->name;?>">
        </div><br>
        <div>
            <label for="email">Email</label>
            <input type="text" value="<?php echo $entry->email;?>" disabled>
        </div><br>
        <input type="hidden" name="idToUpdate" value="<?php echo $id;?>">
        <input type="hidden" name="action" value="update_entry">
        <input type="hidden" name="update_nonce" value="<?php echo wp_create_nonce('update_nonce');?>">
        <input type="submit" value="Soumettre" class="button action">
    </form>
    <?php
    exit;
}


/**
 * on met à jour une entrée dans la bdd
 * 
 * do_my_stuffs_when_entry_update_link_is_clicked
 * @return void
 */
function do_my_stuffs_when_entry_update_link_is_clicked()
{
    // l'action est celle attendue
    if($_POST['action'] == 'update_entry')
    {
        if(!wp_verify_nonce($_POST['update_nonce'],'update_nonce'))
        {
            // le nonce n'a pas été validé ,on redirige vers l'endroit d'où l'utilisateur vient
            wp_redirect(wp_get_referer());
            
        }

        // on nettoie l'id à modifier et le $name
        $idToUpdate = intval($_POST['idToUpdate']);
        $name = sanitize_text_field($_POST['name']);

        // on récupère la connexion à la bdd
        global $wpdb;

        // on fait la requête de modification
        $wpdb->query(
            $wpdb->prepare("
                UPDATE {$wpdb->prefix}email_list
                SET name=%s
                WHERE id=%d",
            array($name,$idToUpdate))
        );
        
        // on redirige vers la liste d'email
        wp_redirect(admin_url("admin.php?page=wp-email-list"));
    }
}
add_action( 'admin_post_update_entry', 'do_my_stuffs_when_entry_update_link_is_clicked' );



/**
 * supprime une entrée dand la bdd
 * 
 * do_my_stuffs_when_entry_delete_link_is_clicked
 * @return void
 */
function do_my_stuffs_when_entry_delete_link_is_clicked()
{
    // l'action est celle attendue
    if($_POST['action'] === 'delete_entry')
    {
        // on redirige si le nonce n'est pas validé
        if(!wp_verify_nonce($_POST['delete_entry_nonce'],'delete_entry_nonce')){
            wp_redirect(wp_get_referer());
        }

        // on récupère la connexion à la bdd et l'id à supprimer
        global $wpdb;
        $idToDelete = intval($_POST['idToDelete']);

        // on fait une requête pour savoir si l'entrée existe en bdd
        $entry_exists = $wpdb->get_row(
            $wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}email_list",
                array($idToDelete))
        );

        if($entry_exists)
        {
            // l'entrée existe, donc on la supprime
            $wpdb->query(
                $wpdb->prepare("
                    DELETE FROM {$wpdb->prefix}email_list
                    WHERE id=%d",
                    array($idToDelete))
            );

            // on redirige vers la liste d'entrées
            wp_redirect(admin_url('admin.php?page=wp-email-list'));
        }
    }
    
}
add_action( 'admin_post_delete_entry', 'do_my_stuffs_when_entry_delete_link_is_clicked' );




////////////////////////////
// FIN ADMINISTRATION
////////////////////////////






