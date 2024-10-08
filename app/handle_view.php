<?php
namespace mihanpanel\app;
class handle_view
{
    private static function handle_middlewares($middlewares)
    {
        if(!$middlewares || !is_array($middlewares))
        {
            return true;
        }
        foreach($middlewares as $middleware)
        {
            if(!$middleware)
            {
                continue;
            }
            $class = '';
            $method = '';
            $reverse_mode = false;
            if(is_array($middleware))
            {
                $class = $middleware[0];
                $method = $middleware[1];

                // check for reverse mode
                if(isset($middleware[2]))
                {
                    $reverse_mode = true;
                }
            }else{
                list($class, $method) = explode('::', $middleware);
            }
            if(strpos($class, '\\') !== 0)
            {
                $class = '\\' . $class;
            }
            if(class_exists($class) && method_exists($class, $method))
            {
                $res = call_user_func([$class, $method]);
                if($reverse_mode && $res)
                {
                    return false;
                }
                if(!$res && !$reverse_mode)
                {
                    return false;
                }
            }
        }
        return true;
    }
    static function handle_panel_content()
    {
        $middlewares = [
            // check account is active
            [__CLASS__, 'is_account_active'],
        ];
        $middlewares = apply_filters('mwpl_middlewares/panel_content', $middlewares);
        $middleware_res = self::handle_middlewares($middlewares);
        if($middleware_res)
        {
            panel::panel_content();
        }
    }
    static function is_account_active()
    {
        $status = users::is_active_account(get_current_user_id());
        if(!$status)
        {
            // load inactive account view
            $view = views::get('user.inactive_account');
            include $view;
            return false;
        }
        return true;
    }
    static function handle_option_panel_view($active_tab)
    {
        $middlewares = apply_filters('mwpl_middleware/option_panel', []);
        $middleware_res = self::handle_middlewares($middlewares);
        if($middleware_res)
        {
            $view = views::get('admin.panel-section.' . $active_tab);
            $view ? include $view : null;
        }
    }
    static function handle_panel_widgets()
    {
        $widgets = [
            'register_day' => [
                'class' => \mihanpanel\app\handle_view::class,
                'method' => 'handle_dashboard_widget_register_day',
            ],
            'comment' => [
                'class' => \mihanpanel\app\handle_view::class,
                'method' => 'handle_dashboard_widget_comment',
            ],
            'edd' => [
                'class' => \mihanpanel\app\handle_view::class,
                'method' => 'handle_dashboard_widget_edd',
            ],
            'woocommerce' => [
                'class' => \mihanpanel\app\handle_view::class,
                'method' => 'handle_dashboard_widget_woocommerce',
            ],
            'awesome_support' => [
                'class' => \mihanpanel\app\handle_view::class,
                'method' => 'handle_dashboard_widget_awesome_support',
            ],
            'poshtvan' => [
                'class' => \mihanpanel\app\handle_view::class,
                'method' => 'handle_dashboard_widget_poshtvan',
            ]
        ];
        $widgets = apply_filters('mihanpanel/panel/dashboard/widgets', $widgets);
        if(!$widgets || !is_array($widgets))
        {
            return false;
        }
        foreach($widgets as $widgetKey => $callBack)
        {
            if(isset($callBack['class']))
            {
                // handle with class
                if(method_exists($callBack['class'], $callBack['method']))
                {
                    call_user_func([$callBack['class'], $callBack['method']]);
                }
            }else{
                // handle with function
                if(function_exists($callBack['method']))
                {
                    call_user_func($callBack['method']);
                }
            }
        }
    }
    static function handle_dashboard_widget_register_day()
    {
        $middlewares = apply_filters('mwpl_panel_widgets_middlewares_register_day', []);
        $res = self::handle_middlewares($middlewares);
        if($res)
        {
            self::render_dashboard_widget_register_day();
        }
    }
    static function handle_dashboard_widget_comment()
    {
        $middlewares = apply_filters('mwpl_panel_widgets_middlewares_comment', []);
        $res = self::handle_middlewares($middlewares);
        if($res)
        {
            self::render_dashboard_widget_comment();
        }
    }

    static function handle_dashboard_widget_edd()
    {
        $middlewares = [
            ['\mihanpanel\app\tools', 'is_edd_active']
        ];
        $middlewares = apply_filters('mwpl_panel_widgets_middlewares_edd', $middlewares);
        $res = self::handle_middlewares($middlewares);
        if($res)
        {
            self::render_dashboard_widget_edd();
        }
    }
    static function handle_dashboard_widget_woocommerce()
    {
        $middlewares = [
            ['\mihanpanel\app\tools', 'is_woocommerce_active']
        ];
        $middlewares = apply_filters('mwpl_panel_widgets_middlewares_woocommerce', $middlewares);
        $res = self::handle_middlewares($middlewares);
        if($res)
        {
            self::render_dashboard_widget_woocommerce();
        }
    }
    static function handle_dashboard_widget_awesome_support()
    {
        $middlewares = [
            ['\mihanpanel\app\tools', 'is_awesome_support_active']
        ];
        $middlewares = apply_filters('mwpl_panel_widgets_middlewares_awesome_support', $middlewares);
        $res = self::handle_middlewares($middlewares);
        if($res)
        {
            self::render_dashboard_widget_awesome_support();
        }
    }
    static function handle_dashboard_widget_poshtvan()
    {
        $middlewares = [
            ['\mihanpanel\app\tools', 'is_poshtvan_active']
        ];
        $middlewares = apply_filters('mwpl_panel_widgets_middlewares_mihanticket', $middlewares);
        $middlewares = apply_filters('mwpl_panel_widgets_middlewares_poshtvan', $middlewares);
        $res = self::handle_middlewares($middlewares);
        if($res)
        {
            self::render_dashboard_widget_poshtvan();
        }
    }

    static function render_dashboard_widget_register_day()
    {
        ?>
        <div class="col-md-4">
            <div class="mihanpanel-card mihanpanel-card-stats">
                <div class="mihanpanel-card-header" data-background-color="orange">
                    <img src="<?php echo MW_MIHANPANEL_URL; ?>/img/cup.svg" width="48" height="48"/>
                </div>
                <div class="mihanpanel-card-content">
                  <h3 class="title"><?php
                      $today_obj      = new \DateTime( date( 'Y-m-d', strtotime( 'today' ) ) );
                      $register_date  = get_the_author_meta( 'user_registered', get_current_user_id() );
                      $registered_obj = new \DateTime( date( 'Y-m-d', strtotime( $register_date ) ) );
                      $interval_obj   = $today_obj->diff( $registered_obj );
                      $days = '';                      
                      if( $interval_obj->days > 0 ) {
                        $days = sprintf(_x('%d Days', 'dashboard-widget-card', 'mihanpanel'), $interval_obj->days);
                      } elseif( 0 == $interval_obj->days ) {
                        $days = sprintf(__('%d Day', 'mihanpanel'), 1);
                      }
                      echo $days;
                      ?></h3>
                    <p class="category"><?php esc_html_e("You are our user", "mihanpanel") ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    static function render_dashboard_widget_comment()
    {
        ?>
        <div class="col-md-4">
            <div class="mihanpanel-card mihanpanel-card-stats">
                <div class="mihanpanel-card-header" data-background-color="pink">
                  <img src="<?php echo MW_MIHANPANEL_URL; ?>/img/comments.svg" width="48" height="48"/>
                </div>
                <div class="mihanpanel-card-content">
                  <h3 class="title"><?php
                      global $wpdb;
                      $userId = get_current_user_id();
                      $where = 'WHERE comment_approved = 1 AND user_id = ' . $userId;
                      $comment_count = $wpdb->get_var("SELECT COUNT( * ) AS total
                          FROM {$wpdb->comments}
                          {$where}");
                      echo esc_html($comment_count);
                      ?></h3>
                    <p class="category"><?php esc_html_e("Your Comments", "mihanpanel") ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    static function render_dashboard_widget_edd()
    {
        ?>
            <div class="col-md-4">
                <div class="mihanpanel-card mihanpanel-card-stats">
                    <div class="mihanpanel-card-header" data-background-color="red">
                      <img src="<?php echo MW_MIHANPANEL_URL; ?>/img/files.svg" width="48" height="48"/>
                    </div>
                    <div class="mihanpanel-card-content">
                      <h3 class="title">
                          <?php $user_id = get_current_user_id();
                          $mwpr_purchased = edd_get_users_purchases($user_id);
                          $counter = 0;
                          if ($mwpr_purchased) {
                              foreach ($mwpr_purchased as $val) {
                                  foreach ($val as $k => $v) {
                                      if ($k == 'ID') {
                                          $mwpr_name = edd_get_payment_meta_cart_details($v);
                                          foreach ($mwpr_name as $mwprt_name) {
                                              $counter++;
                                          }
                                      }
                                  }
                              }
                          }
                          echo esc_html($counter);
                          ?>
                      </h3>
                        <p class="category"><?php esc_html_e("Purchased files", "mihanpanel"); ?></p>
                    </div>
                </div>
            </div>
        <?php
    }
    static function render_dashboard_widget_woocommerce()
    {
        ?>
        <div class="col-md-4">
            <div class="mihanpanel-card mihanpanel-card-stats">
                <div class="mihanpanel-card-header" data-background-color="blue">
                  <img src="<?php echo MW_MIHANPANEL_URL; ?>/img/boxes.svg" width="48" height="48"/>
                </div>
                <div class="mihanpanel-card-content">
                  <h3 class="title"><?php $user_id = get_current_user_id();
                    echo esc_html(wc_get_customer_order_count($user_id)); ?></h3>
                <p class="category"><?php esc_html_e("Your purchase count", "mihanpanel"); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    static function render_dashboard_widget_awesome_support()
    {
        ?>
        <div class="col-md-4">
            <div class="mihanpanel-card mihanpanel-card-stats">
                <div class="mihanpanel-card-header" data-background-color="green">
                  <img src="<?php echo MW_MIHANPANEL_URL; ?>/img/tickets.svg" width="48" height="48"/>
                </div>
                <div class="mihanpanel-card-content">
                  <h3 class="title">
                      <?php
                      $args = array(
                          'author' => get_current_user_id(),
                          'post_type' => 'ticket'
                      );
                      $posts = new \WP_Query($args);
                      echo esc_html($posts->found_posts);
                      ?>
                  </h3>
                    <p class="category"><?php esc_html_e("Your tickets", 'mihanpanel'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    static function render_dashboard_widget_poshtvan()
    {
        ?>
        <div class="col-md-4">
            <div class="mihanpanel-card mihanpanel-card-stats">
                <div class="mihanpanel-card-header" data-background-color="green">
                  <img src="<?php echo MW_MIHANPANEL_URL; ?>/img/tickets.svg" width="48" height="48"/>
                </div>
                <div class="mihanpanel-card-content">
                  <h3 class="title">
                      <?php
                      echo esc_html(\mihanpanel\app\adapter\mihanticket::get_user_tickets_count(get_current_user_id()));
                      ?>
                  </h3>
                    <p class="category"><?php esc_html_e("Your tickets", 'mihanpanel'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    static function run_alternative_method($alternative_method)
    {
        return $alternative_method && method_exists(__CLASS__, $alternative_method) ? call_user_func([__CLASS__, $alternative_method]) : self::show_go_pro_link();
    }
    static function handle_option_panel_render_method($method, $alternative_method=false)
    {
        if(!$method || !is_array($method))
        {
            return self::run_alternative_method($alternative_method);
        }
        if(class_exists($method[0]) && method_exists($method[0], $method[1]))
        {
            return call_user_func([$method[0], $method[1]]);
        }
        return self::run_alternative_method($alternative_method);
    }
    static function handle_fields_render_method($method, $alternative_method=false, ...$args)
    {
        if(!$method || !is_array($method))
        {
            return self::run_alternative_method($alternative_method);
        }
        if(class_exists($method[0]) && method_exists($method[0], $method[1]))
        {
            return call_user_func([$method[0], $method[1]], ...$args);
        }
        return self::run_alternative_method($alternative_method);
    }
    static function show_go_pro_link()
    {
        $pro_version_link = tools::get_pro_version_link();
        ?>
        <span class="mpproversion"><a target="blank" href="<?php echo esc_url($pro_version_link); ?>"><?php _e("Required Pro Version", "mihanpanel");?></a></span>
        <?php
    }
    static function option_panel_field_login_form_theme()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/login_form_theme', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_login_form_position(){
        $render_method = apply_filters('mwpl_option_panel/render_method/login_form_position', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_login_button_text_color()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/login_button_text_color', []);
        self::handle_option_panel_render_method($render_method, 'option_panel_field_login_button_text_color_alternative');
    }
    static function option_panel_field_login_button_text_color_alternative()
    {
        ?>
        <div class="mp_option_field">
            <label><?php esc_html_e("Login Button Text Color", "mihanpanel");?></label>
            <?php self::show_go_pro_link()?>
            <p class="description"><?php esc_html_e("Color of Login and Register button text", "mihanpanel"); ?></p>
        </div>
        <?php
    }
    static function option_panel_field_register_button_bg_color()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/register_button_bg_color', []);
        self::handle_option_panel_render_method($render_method, 'option_panel_field_register_button_bg_color_alternative');
    }
    static function option_panel_field_register_button_bg_color_alternative()
    {
        ?>
        <div class="mp_option_field">
            <label><?php esc_html_e("Register button background color", "mihanpanel");?></label>
            <?php self::show_go_pro_link()?>
            <p class="description"><?php esc_html_e("You can change background color of register button.", "mihanpanel"); ?></p>
        </div>
        <?php
    }

    static function option_panel_field_register_button_text_color()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/register_button_text_color', []);
        self::handle_option_panel_render_method($render_method, 'option_panel_field_register_button_text_color_alternative');
    }
    static function option_panel_field_register_button_text_color_alternative()
    {
        ?>
        <div class="mp_option_field">
            <label><?php esc_html_e("Register button text color", "mihanpanel");?></label>
            <?php self::show_go_pro_link()?>
            <p class="description"><?php esc_html_e("By changing text color, the border color will also change.", "mihanpanel"); ?></p>
        </div>
        <?php
    }
    
    static function option_panel_field_login_fields_bg_color()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/login_fields_bg_color', []);
        self::handle_option_panel_render_method($render_method, 'option_panel_field_login_fields_bg_color_alternative');
    }
    static function option_panel_field_login_fields_bg_color_alternative()
    {
        ?>
        <div class="mp_option_field">
            <label><?php esc_html_e("Login Fields Background Color", "mihanpanel");?></label>
            <?php self::show_go_pro_link()?>
            <p class="description"><?php esc_html_e("Color of Login and Register fields", "mihanpanel"); ?></p>
        </div>
        <?php
    }
    static function option_panel_field_login_labels_color()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/login_fields_label_color', []);
        self::handle_option_panel_render_method($render_method, 'option_panel_field_login_labels_color_alternative');
    }
    static function option_panel_field_login_labels_color_alternative()
    {
        ?>
        <div class="mp_option_field">
            <label><?php esc_html_e("Login Field's Label Color", "mihanpanel");?></label>
            <?php self::show_go_pro_link()?>
            <p class="description"><?php esc_html_e("Color of Login and Register labels", "mihanpanel"); ?></p>
        </div>
        <?php
    }
    static function option_panel_field_is_redirect_wc_checkout_to_login()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/general_is_redirect_wc_checkout_page_to_login_page', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_is_add_mihanpanel_to_wp_nav_menu()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/general_is_add_mihanpanel_to_wp_nav_menu', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_mihanpanel_wp_nav_menu()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/general_mihanpanel_wp_nav_menu', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_login_fields_text_color()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/login_fields_text_color', []);
        self::handle_option_panel_render_method($render_method, 'option_panel_field_login_fields_text_color_alternative');
    }
    static function option_panel_field_login_fields_text_color_alternative()
    {
        ?>
        <div class="mp_option_field">
            <label><?php esc_html_e("Login Fields Text Color", "mihanpanel");?></label>
            <?php self::show_go_pro_link()?>
            <p class="description"><?php esc_html_e("Color of Login and Register fields text", "mihanpanel"); ?></p>
        </div>
        <?php
    }
    static function option_panel_field_login_info_messagebox_bg_color()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/login_info_messagebox_bg_color', []);
        self::handle_option_panel_render_method($render_method, 'option_panel_field_login_info_messagebox_bg_color_alternative');
    }
    static function option_panel_field_login_info_messagebox_bg_color_alternative()
    {
        ?>
        <div class="mp_option_field">
            <label><?php esc_html_e("Login Info Messagebox Background Color", "mihanpanel");?></label>
            <?php self::show_go_pro_link()?>
            <p class="description"><?php esc_html_e("Background Color of Login and Register Info Messagebox", "mihanpanel"); ?></p>
        </div>
        <?php
    }
    static function option_panel_field_login_info_messagebox_text_color()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/login_info_messagebox_text_color', []);
        self::handle_option_panel_render_method($render_method, 'option_panel_field_login_info_messagebox_text_color_alternative');
    }
    static function option_panel_field_login_info_messagebox_text_color_alternative()
    {
        ?>
        <div class="mp_option_field">
            <label><?php esc_html_e("Login Info Messagebox Text Color", "mihanpanel");?></label>
            <?php self::show_go_pro_link()?>
            <p class="description"><?php esc_html_e("Color of Login and Register Info Messagebox Text", "mihanpanel"); ?></p>
        </div>
        <?php
    }

    static function option_panel_field_redirect_normal_user()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/redirect_normal_user', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_redirect_url_after_logout()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/redirect_url_after_logout', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_use_mihan_avatar()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/use_mihan_avatar', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_exclude_login_route_in_litespeed_cache()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/exclude_login_route_in_litespeed_cache', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_panel_theme()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/panel_theme', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_panel_font()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/panel_font', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_off_code_text()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/off_code_text', []);
        self::handle_option_panel_render_method($render_method);
    }

    static function option_panel_field_sidebar_bg_color()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/sidebar_bg_color', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_sidebar_color()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/sidebar_color', []);
        self::handle_option_panel_render_method($render_method);
    }


    static function option_panel_field_is_show_register_day_count_widget()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/is_show_register_day_count_widget', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_is_show_comment_widget()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/is_show_comment_widget', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_is_show_woocommerce_widget()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/is_show_woocommerce_widget', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_is_show_edd_widget()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/is_show_edd_widget', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_is_show_awesome_support_widget()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/is_show_awesome_support_widget', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_is_show_poshtvan_widget()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/is_show_poshtvan_widget', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_ban_roles()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/ban_roles', []);
        self::handle_option_panel_render_method($render_method, 'option_panel_field_ban_roles_alternative');
    }
    static function option_panel_field_ban_roles_alternative()
    {
        ?>
        <div class="mp_option_single">
            <?php
                $all_roles = wp_roles()->get_names();
                foreach ($all_roles as $role_key => $role_name):
                    if ($role_key == 'administrator')
                        continue;
            ?>
                <p>
                    <label for="<?php echo esc_attr($role_key) ?>"><?php _e($role_name); ?></label>
                    <?php self::show_go_pro_link(); ?>
                </p>
            <?php endforeach; ?>
        </div>
        <?php
    }
    static function option_panel_field_is_blog_section_enable()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/is_blog_section_enable', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_blog_section_title()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/blog_section_title', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_blog_section_title_link()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/blog_section_title_link', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_blog_section_posts_category()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/blog_section_posts_category', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_blog_section_posts_count()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/blog_section_posts_count', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_login_top_message()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/login_form_top_message', []);
        self::handle_option_panel_render_method($render_method);
    }
    // start recent products
    static function option_panel_field_is_wc_recent_products_enable()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/is_wc_recent_products_enable', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_wc_recent_products_section_title()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/wc_recent_products_section_title', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_wc_recent_products_section_link()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/wc_recent_products_section_link', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_wc_recent_products_section_items_category()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/wc_recent_products_section_items_category', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_wc_products_section_items_count()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/wc_products_section_items_count', []);
        self::handle_option_panel_render_method($render_method);
    }
    // end recent products

    // register tab
    static function option_panel_field_register_text()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/register_text', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_roles_user_can_select()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/roles_user_can_select', []);
        self::handle_option_panel_render_method($render_method, 'option_panel_field_roles_user_can_select_alternative');
    }
    static function option_panel_field_roles_user_can_select_alternative()
    {
        ?>
        <div class="mp_option_field">
            <?php $roles_name = wp_roles()->get_names();
            foreach ($roles_name as $role_key => $role_name):
                if ($role_key == 'administrator')
                    continue;
                ?>
                <p>
                    <label><?php echo translate_user_role($role_name); ?></label>
                    <?php self::show_go_pro_link(); ?>
                </p>
            <?php endforeach; ?>
        </div>
        <?php
    }

    // login with sms tab
    static function option_panel_field_wpml_sms_just_in_persian()
    {
        if(!tools::is_wpml_active())
        {
            esc_html_e('This plugin is not active on your site', 'mihanpanel');
            return false;
        }
        $render_method = apply_filters('mwpl_option_panel/render_method/wpml_sms_just_in_persian', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_force_get_phone_number_in_smart_login_mode()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/force_get_phone_number_in_smart_login_mode', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_edit_username_field_label_in_login_form()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/edit_login_fields_labels/username', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_username_field_label_value_in_login_form()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/edit_login_fields_labels/username_value', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_edit_phone_field_label_in_login_form()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/edit_login_fields_labels/phone', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_phone_field_label_value_in_login_form()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/edit_login_fields_labels/phone_value', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_edit_password_field_label_in_login_form()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/edit_login_fields_labels/password', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_password_field_label_value_in_login_form()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/edit_login_fields_labels/password_value', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_edit_dynamic_code_field_label_in_login_form()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/edit_login_fields_labels/dynamic_code', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_dynamic_code_field_label_value_in_login_form()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/edit_login_fields_labels/dynamic_code_value', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_edit_login_button_text_in_login_form()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/edit_login_fields_labels/login_button', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_login_button_text_value_in_login_form()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/edit_login_fields_labels/login_button_text_value', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_edit_register_button_text_in_login_form()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/edit_login_fields_labels/register_button', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_register_button_text_value_in_login_form()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/edit_login_fields_labels/register_button_text_value', []);
        self::handle_option_panel_render_method($render_method);
    }
    
    static function option_panel_smart_login()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/smart_login', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_smart_login_generate_random_username()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/smart_login_generate_random_username', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_login_with_google()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/login_with_google', []);
        self::handle_option_panel_render_method($render_method, 'option_panel_field_login_with_google_alternative');
    }
    static function option_panel_field_login_with_google_alternative()
    {
        ?>
        <div class="mp_option_field">
            <div>
                <label><?php echo esc_html__('Activate login with Google', 'mihanpanel') ?></label>
                <input type="checkbox">
            </div>
        </div>
        <?php
    }
    static function option_panel_field_password_type()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/password_type', []);
        self::handle_option_panel_render_method($render_method, 'option_panel_field_password_type_alternative');
    }

    static function option_panel_field_password_type_alternative()
    {
        ?>
        <div class="mp_option_field">
            <?php
            $password_types = pro_version_detail::get_password_type_title();
            ?>
            <?php foreach($password_types as $name):?>
            <p>
                <label><?php echo esc_html($name); ?></label>
                <?php self::show_go_pro_link(); ?>
            </p>
            <?php endforeach; ?>
        </div>
        <?php
    }
    static function option_panel_hide_email_field_in_register_form()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/hide_email_in_register_form', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_hide_username_field_in_register_form()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/hide_username_field_in_register_form', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_hide_password_field_in_register_form()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/hide_password_field_in_register_form', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_user_phone_section()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/user_phone_section', []);
        self::handle_option_panel_render_method($render_method, 'option_panel_user_phone_section_alternative');
    }
    static function option_panel_user_phone_section_alternative()
    {
        ?>
        <div class="mp_option_field">
            <p>
                <label><?php _e('Auto confirm phone number after user register', 'mihanpanel'); ?></label>
                <?php self::show_go_pro_link(); ?>
            </p>
            <p>
                <label><?php _e('User can change phone number', 'mihanpanel'); ?></label>
                <?php self::show_go_pro_link(); ?>
            </p>
        </div>
        <?php
    }
    static function option_panel_field_sms_provider()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/sms_provider', []);
        self::handle_option_panel_render_method($render_method);
    }

    static function option_panel_field_phone_validation_sms_content()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/phone_validation_sms_content', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_dynamic_code_sms_content()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/dynamic_code_sms_content', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_login_header_additional_code()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/login_header_additional_code', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_transfer_digits_phone_numbers()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/transfer_digits_phone_numbers', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_delete_ghost_users()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/delete_ghost_users', []);
        self::handle_option_panel_render_method($render_method);
    }

    static function render_user_fields_type_selectbox($field_name, $selected=false)
    {
        $field_types = user_fields::get_types();
        ?>
        <select name="<?php echo esc_attr($field_name); ?>">
            <?php foreach($field_types as $type => $item): ?>
                <option <?php $selected ? selected($type, $selected) : null;?> <?php echo $item['disabled'] ? 'disabled' : false; ?> value="<?php echo esc_attr($type)?>"><?php echo esc_html($item['title'])?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    static function render_panel_tabs_fields_field_id($menu)
    {
        $render_method = apply_filters('mwpl_admin_tabs_menu/render_method/field_id', []);
        self::handle_fields_render_method($render_method, 'render_panel_tabs_fields_field_id_alternative', $menu);
    }
    static function render_panel_tabs_fields_field_id_alternative()
    {
        ?>
        <div class="pro-input-holder" labels="<?php _e("Pro Version", "mihanpanel")?>">
            <?php self::show_go_pro_link()?>
            <input disabled type="text">
        </div>
        <?php
    }
    static function render_panel_tabs_fields_is_show_by_role($menu)
    {
        $render_method = apply_filters('mwpl_admin_tabs_menu/render_method/is_show_by_role', []);
        self::handle_fields_render_method($render_method, false, $menu);
    }

    static function option_panel_field_dynamic_code_email_subject()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/dynamic_code_email_subject', []);
        self::handle_option_panel_render_method($render_method);
    }
    static function option_panel_field_dynamic_code_email_content()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/dynamic_code_email_content', []);
        self::handle_option_panel_render_method($render_method);
    }

    static function option_panel_custom_captcha()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/custom_captcha', []);
        self::handle_option_panel_render_method($render_method, 'option_panel_custom_captcha_alternative');
    }
    static function option_panel_custom_captcha_alternative()
    {
        ?>
        <div class="mp_option_field">
            <label><?php echo esc_html__('Activate Captcha', 'mihanpanel') ?></label>
            <?php self::show_go_pro_link()?>
        </div>
        <?php
    }

    static function option_panel_google_recaptcha()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/google_recaptcha', []);
        self::handle_option_panel_render_method($render_method, 'option_panel_google_recaptcha_alternative');
    }
    static function option_panel_google_recaptcha_alternative()
    {
        ?>
        <div class="mp_option_field">
            <label><?php echo esc_html__('Activate Recaptcha', 'mihanpanel') ?></label>
            <?php self::show_go_pro_link()?>
        </div>
        <?php
    }

    static function option_panel_reset_password()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/reset_password', []);
        self::handle_option_panel_render_method($render_method, 'render_option_panel_field_reset_password_alternative');
    }

    public static function render_option_panel_field_reset_password_alternative()
    {
        $is_active = options::get_reset_password_status();
        ?>
        <div class="mp_option_field">
            <div>
                <label for="mp_disable_reset_password"><?php echo esc_html__('Disable reset password', 'mihanpanel') ?></label>
                <input type="checkbox" name="mp_disable_reset_password" id="mp_disable_reset_password" value="1" <?php echo apply_filters('mp_disable_reset_password_checked', checked('1', $is_active, false)) ?>>
            </div>
        </div>
        <?php
    }

    static function option_panel_login_body_background_fields()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/login_page_background', []);
        self::handle_option_panel_render_method($render_method);
    }

    static function option_panel_login_form_background_fields()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/login_form_background', []);
        self::handle_option_panel_render_method($render_method, 'handle_option_panel_login_form_background_fields_alternative');
    }

    public static function handle_option_panel_login_form_background_fields_alternative()
    { ?>
        <div class="mp_option_single">
            <label><?php esc_html_e("Login and Register form background", "mihanpanel"); ?></label>
            <input style="text-align:left;direction:ltr" id="mp_bg_image" type="text" name="mp_bg_image" value="<?php echo esc_attr(get_option('mp_bg_image')); ?>" />
            <input id="mp_upload_bg_image_button" type="button" class="button-primary" value="<?php esc_attr_e('Upload Image', 'mihanpanel'); ?>" />
        </div>
    <?php
    }

    static function option_panel_files_allowed_upload()
    {
        $render_method = apply_filters('mwpl_option_panel/render_method/files_allowed_upload', []);
        self::handle_option_panel_render_method($render_method, 'option_panel_files_allowed_upload_alternative');
    }
    static function option_panel_files_allowed_upload_alternative()
    {
        ?>
        <div class="mp_option_field">
            <label><?php _e('Upload File', 'mihanpanel'); ?></label>
            <?php self::show_go_pro_link()?>
        </div>
        <?php
    }

    static function optionPanelContentMiddlewares($middlewares)
    {
        // check lciense
        $middlewares[] = [__CLASS__, 'handleAdminNoticesInOptionPanel'];
        return $middlewares;
    }
    static function handleAdminNoticesInOptionPanel()
    {
        $notices = notice::once_get_multiple_notice();
        if($notices)
        {
            foreach($notices as $noticeItem)
            {
                $notice = sprintf('<p class="alert %s">%s</p>', $noticeItem['type'], $noticeItem['msg']);
                echo $notice;
            }
            
        }
        return true;
    }
}
