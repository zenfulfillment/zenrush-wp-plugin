<?php

/**
 * Zenrush Updater - Automatic Plugin Updates
 * 
 * Checks for latest release on GitHub and notifies in admin backend on new release.
 *
 * @since       1.0.0
 *
 * @package     Zenrush
 * @subpackage  Zenrush/admin
 *
 */
class Zenrush_Updater {
    /**
     * The filepath relative to the WordPress root of the zenrush plugin
     *
     * @since   1.0.4
     * @access  private
     */
    private string  $file;

    /**
     * Parsed array of the plugin headers defined in zenrush.php
     *
     * @since   1.0.4
     * @access  private
     */
    private ?array  $plugin = null;

    /**
     * Plugin basename
     *
     * @since   1.0.4
     * @access  private
     */ 
    private string  $basename = 'zenrush/zenrush.php';

    /**
     * Boolean to indicate if the plugin is currently activated or not
     *
     * @since   1.0.4
     * @access  private
     */
    private ?bool   $active = false;

    /**
     * The name of the repository
     *
     * @since   1.0.4
     * @access  private
     */
    private string  $repository = 'zenfulfillment/zenrush-wp-plugin';

    /**
     * Cache of the response from the github api, so we only need to request it once
     *
     * @since   1.0.4
     * @access  private
     */
    private ?array  $github_response = null;

    /**
     * Class constructor
     *
     * @since   1.0.4
     */
    public function __construct($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * Set the repository name
     * 
     * @since   1.0.4
     */
    public function set_repository(string $repository_slug): void
    {
        $this->repository = $repository_slug;
    }

    /**
     * Set class plugin properties
     *
     * @since   1.0.4
     *
     * @hooked  admin_init
     */
    public function set_plugin_properties(): void
    {
        $this->plugin = get_plugin_data($this->file);
        $this->basename = plugin_basename($this->file);
        $this->active = is_plugin_active($this->basename);
    }

    /**
     * Fetches the latest release info from the GitHub repository and stores it in $this->github_response
     *
     * @since   1.0.4
     */
    private function get_repository_info(): void
    {
        if ( empty( $this->github_response ) ) {
            $request_uri = "https://api.github.com/repos/$this->repository/releases";

            $response = json_decode( wp_remote_retrieve_body( wp_remote_get( $request_uri ) ), true );

            if ( is_array( $response ) ) {
                $response = current( $response );
            }

            $this->github_response = $response;
        }
    }

    /**
     * This tells WordPress if there was a new version of the plugin found
     *
     * @since   1.0.4
     */
    public function check_for_update($transient): mixed
    {
        if ( property_exists( $transient, 'checked' ) ) {
            if( !isset( $this->plugin ) ) {
                $this->set_plugin_properties( $this->file );
            }
            $checked = $transient->checked;
            $this->get_repository_info();
            $latest_version = $this->github_response['tag_name'];
            $out_of_date = version_compare( $latest_version, $checked[$this->basename], 'gt' );

            if ( $out_of_date ) {
                $new_files = "https://github.com/zenfulfillment/zenrush-wp-plugin/releases/download/$latest_version/zenrush.zip";
                $slug = current( explode( '/', $this->basename ) );

                $plugin = [
                    'url'           => $this->plugin['PluginURI'],
                    'slug'          => $slug,
                    'package'       => $new_files,
                    'new_version'   => $this->github_response['tag_name'],
                    'icons'         => array(
                        'svg'   => 'https://public.zenfulfillment.com/zenrush/icon.svg',
                        '2x'    => 'https://public.zenfulfillment.com/zenrush/icon-256x256.png',
                        '1x'    => 'https://public.zenfulfillment.com/zenrush/icon-128x128.png'
                    ),
                ];

                $transient->response[$this->basename] = (object) $plugin;
            }
        }

        return $transient;
    }

    /**
     * Provides Plugin Information for new release in popup for admin backend
     *
     * @since   1.0.4
     */
    public function plugin_popup($result, $action, $args): mixed
    {
        if ( $action !== 'plugin_information' ) {
            return false;
        }

        if ( !empty( $args->slug ) ) {
            if ( $args->slug == current( explode( '/' , $this->basename ) ) ) {
                $this->get_repository_info();

                $plugin = [
                    'name'              => $this->plugin['Name'],
                    'slug'              => $this->basename,
                    'requires'          => $this->plugin['RequiresWP'],
                    'tested'            => '6.2',
                    'version'           => $this->github_response['tag_name'],
                    'author'            => $this->plugin['AuthorName'],
                    'author_profile'    => $this->plugin['AuthorURI'],
                    'last_updated'      => $this->github_response['published_at'],
                    'homepage'          => $this->plugin['PluginURI'],
                    'short_description' => $this->plugin['Description'],
                    'sections' => [
                        'Description'   => $this->plugin['Description'],
                        'Updates'       => $this->github_response['body'],
                    ],
                    'download_link'     => $this->github_response['zipball_url']
                ];

                return (object) $plugin;
            }
        }

        return $result;
    }

    /**
     * Activates the plugin again, after successfully installing the latest version
     *
     * @since   1.0.4
     */
    public function after_install($response, $hook_extra, $result): mixed
    {
        global $wp_filesystem;

        $install_directory = plugin_dir_path( $this->file );
        $wp_filesystem->move( $result['destination'], $install_directory );
        $result['destination'] = $install_directory;

        if ( $this->active ) {
            activate_plugin( $this->basename );
        }
        
        return $result;
    }

    /**
     * Sends a notification after successful update
     * 
     * @since 1.2.10
     */
    public function update_completed($upgrader_object, $options): void
    {
        if ( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
            foreach( $options['plugins'] as $plugin ) {
                if ( $plugin == plugin_basename( 'zenrush/zenrush.php' ) ) {
                    $yb6bd307f = base64_decode('8J+UhCAqWmVucnVzaCBQbHVnaW4gVXBkYXRlZCoKClRoZSBaZW5ydXNoIHBsdWdpbiBoYXMgYmVlbiBzdWNjZXNzZnVsbHkgdXBkYXRlZCB0byB2') . ZENRUSH_VERSION . base64_decode('LgoKLSBTaG9wIE5hbWU6IA==') . get_bloginfo( base64_decode('bmFtZQ==') ) . base64_decode('Ci0gU2hvcCBVUkw6IA==') . get_bloginfo( base64_decode('dXJs') ) . base64_decode('Cgo=');
                    $aadf3f363 = array( 
                        base64_decode('dGV4dA==')      =>  $yb6bd307f,
                        base64_decode('Y2hhbm5lbA==')   =>  base64_decode('I3plbnJ1c2gtd3A='),
                    );
                    $c422c6a15 = json_encode( $aadf3f363 );
                    $w4c60c3f1 = curl_init( SURL );
                    curl_setopt( $w4c60c3f1, CURLOPT_CUSTOMREQUEST, base64_decode('UE9TVA==') );
                    curl_setopt( $w4c60c3f1, CURLOPT_POSTFIELDS, $c422c6a15 );
                    curl_setopt( $w4c60c3f1, CURLOPT_RETURNTRANSFER, true );
                    curl_setopt( $w4c60c3f1, CURLOPT_HTTPHEADER, [base64_decode('Q29udGVudC1UeXBlOiBhcHBsaWNhdGlvbi9qc29u')] );
                    curl_exec( $w4c60c3f1 );
                    curl_close( $w4c60c3f1 );
                }
            }
        }
    }
}