<?php

/**
 * Auto updater class - Checks for latest release on GitHub and notifies in admin backend on new release
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
     * @since    1.0.4
     * @access   private
     * @var      string $file The string used to uniquely identify this plugin.
     */
    private string  $file;

    /**
     * Parsed array of the plugin headers defined in zenrush.php
     *
     * @since    1.0.4
     * @access   private
     * @var      string[] $plugin Array of plugin headers defined in zenrush.php
     */
    private ?array   $plugin = null;

    /**
     * Plugin basename
     *
     * @since    1.0.4
     * @access   private
     * @var      string $basename zenrush/zenrush.php
     */
    private string  $basename = 'zenrush/zenrush.php';

    /**
     * Boolean to indicate if the plugin is currently activated or not
     *
     * @since    1.0.4
     * @access   private
     * @var      bool $active If the plugin is activated or not
     */
    private ?bool    $active = false;

    /**
     * The name of the repository
     *
     * @since    1.0.4
     * @access   private
     * @var      string $repository The name of the repository
     */
    private string  $repository = 'zenfulfillment/zenrush-wp-plugin';

    /**
     * Cache of the response from the github api, so we only need to request it once
     *
     * @since    1.0.4
     * @access   private
     * @var      array|null $github_response Cache of the latest response from the github api
     */
    private array|null  $github_response = null;

    /**
     * Class constructor
     *
     * @param $file - Path to plugin file
     */
    public function __construct($file)
    {
        $this->file = $file;
        return $this;
    }

    public function set_repository(string $repository_slug): void
    {
        $this->repository = $repository_slug;
    }

    /**
     * Set class plugin properties
     *
     * @since 1.0.4
     *
     * @hooked admin_init
     * @return void
     */
    public function set_plugin_properties(): void
    {
        $this->plugin = get_plugin_data($this->file);
        $this->basename = plugin_basename($this->file);
        $this->active = is_plugin_active($this->basename);
    }

    /**
     * Fetches the latest release info from GitHub repository and stores it in $this->github_response
     *
     * @since 1.0.4
     *
     * @return void
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
     * @since 1.0.4
     *
     * @param $transient
     * @return mixed
     */
    public function check_for_update($transient): mixed
    {
        if ( property_exists( $transient, 'checked' ) ) {
            if(!isset($this->plugin)) {
                $this->set_plugin_properties($this->file);
            }
            $checked = $transient->checked;
            $this->get_repository_info();
            $latest_version = $this->github_response['tag_name'];
            $out_of_date = version_compare($latest_version, $checked[$this->basename], 'gt');

            if ( $out_of_date ) {
                $new_files = "https://github.com/zenfulfillment/zenrush-wp-plugin/releases/download/$latest_version/zenrush.zip";
                $slug = current(explode('/', $this->basename));

                $plugin = [
                    'url' => $this->plugin['PluginURI'],
                    'slug' => $slug,
                    'package' => $new_files,
                    'new_version' => $this->github_response['tag_name']
                ];

                $transient->response[$this->basename] = (object) $plugin;
            }
        }

        return $transient;
    }

    /**
     * Provides Plugin Information for new release in popup for admin backend
     *
     * @since 1.0.4
     *
     * @param $result
     * @param $action
     * @param $args
     * @return mixed
     */
    public function plugin_popup($result, $action, $args): mixed
    {
        if ( $action !== 'plugin_information' ) {
            return false;
        }

        if ( !empty( $args->slug ) ) {
            if ( $args->slug == current( explode('/' , $this->basename) ) ) {
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
     * Activates the latest plugin version again, after successfully installing it
     *
     * @since 1.0.4
     *
     * @param $response
     * @param $hook_extra
     * @param $result
     * @return mixed
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
}