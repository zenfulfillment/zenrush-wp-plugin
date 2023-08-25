<?php

/**
 * The Zenrush elementor widget
 *
 * @link       https://zenfulfillment.com
 * @since      1.1.6
 *
 * @package    Zenrush
 * @subpackage Zenrush/elementor-widget
 */

class Elementor_Zenrush_Widget extends \Elementor\Widget_Base {

    public function get_name(): string
    {
        return 'zenrush_widget';
    }

    public function get_title(): string
    {
        return esc_html__( 'Zenrush', 'zenrush' );
    }

    public function get_icon(): string
    {
        return 'eicon-flash';
    }

    public function get_script_depends(): array
    {
        return [ 'zf-zenrush' ];
    }

    public function get_categories(): array
    {
        return [ 'zenfulfillment', 'basic' ];
    }

    public function get_keywords(): array
    {
        return [ 'zenfulfillment', 'zenrush', 'premiumversand' ];
    }

    protected function register_controls(): void
    {

        // Content Tab Start
        $this->start_controls_section(
            'section_title',
            [
                'label' => esc_html__( 'Options', 'zenrush' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );


        // Variant setting
        $this->add_control(
            'variant',
            [
                'type' => \Elementor\Controls_Manager::SELECT,
                'label' => esc_html__( 'Variant', 'zenrush' ),
                'default' => 'primary',
                'options' => [
                    'primary' => [
                        'title' => esc_html__( 'Primary', 'zenrush' ),
                    ],
                    'badge' => [
                        'title' => esc_html__( 'Badge', 'zenrush' ),
                    ],
                    'logo' => [
                        'title' => esc_html__( 'Logo', 'zenrush' ),
                    ],
                ],
            ]
        );

        // Language setting
        $this->add_control(
            'locale',
            [
                'type' => \Elementor\Controls_Manager::SELECT,
                'label' => esc_html__( 'Language', 'zenrush' ),
                'default' => 'de',
                'options' => [
                    'de' => [
                        'title' => esc_html__( 'German', 'zenrush' ),
                    ],
                    'en' => [
                        'title' => esc_html__( 'English', 'zenrush' ),
                    ],
                ],
            ]
        );

        // Control to show custom label text on badge variant
        $this->add_control(
            'badge_label',
            [
                'label' => esc_html__( 'Badge Text', 'zenrush' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__( 'Powered by', 'zenrush' ),
                'placeholder' => esc_html__( 'Add the badge text here', 'zenrush' ),
                'condition' => [
                    'variant' => 'badge',
                ],
            ]
        );

        // Show delivery date on badge variant
        $this->add_control(
            'show_delivery_date',
            [
                'label' => esc_html__( 'Show Delivery Date', 'zenrush' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'On', 'zenrush' ),
                'label_off' => esc_html__( 'Off', 'zenrush' ),
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'variant' => 'badge',
                ],
            ]
        );


        $this->end_controls_section();
        // Content Tab End
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();

        $store_id = get_option( 'Zenrush_store_id' );
        $variant = $settings['variant'];
        $locale = $settings['locale'];
        $show_delivery_date = $variant === 'badge' && $settings['show_delivery_date'];

        // element bool attributes without values
        $attrs = array();
        if( $show_delivery_date ) {
            $attrs[] = 'showdeliverydate';
        }

        $has_attrs = count( $attrs ) > 0;
        ?>

        <zf-zenrush
            store="<?php echo $store_id; ?>"
            locale="<?php echo $locale; ?>"
            variant="<?php echo $variant; ?>"
            <?php
                if( $variant === 'badge' && $settings['badge_label'] ) {
                    echo 'label="'. $settings['badge_label'] .'"';
                }
                if( $has_attrs ) {
                    echo implode(" ", $attrs);
                }
            ?>
        >
        </zf-zenrush>

        <?php
    }
}

