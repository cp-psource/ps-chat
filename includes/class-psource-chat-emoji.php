<?php
/**
 * PSource Chat Emoji System
 * 
 * Modular emoji picker with category support and modern UI
 * 
 * @package PSource Chat
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class PSource_Chat_Emoji {
    
    /**
     * Emoji categories with their emojis
     * 
     * @var array
     */
    private $emoji_categories = array();
    
    /**
     * Plugin URL for assets
     * 
     * @var string
     */
    private $plugin_url;
    
    /**
     * Constructor
     */
    public function __construct( $plugin_url = '' ) {
        $this->plugin_url = $plugin_url;
        $this->init_emoji_categories();
    }
    
    /**
     * Initialize emoji categories
     */
    private function init_emoji_categories() {
        $this->emoji_categories = array(
            'smileys' => array(
                'label' => __( 'Smileys & Emotionen', 'psource-chat' ),
                'icon' => '😀',
                'emojis' => array(
                    '😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃',
                    '😉', '😊', '😇', '🥰', '😍', '🤩', '😘', '😗', '☺️', '😚',
                    '😙', '🥲', '😋', '😛', '😜', '🤪', '😝', '🤑', '🤗', '🤭',
                    '🤫', '🤔', '🤐', '🤨', '😐', '😑', '😶', '😏', '😒', '🙄',
                    '😬', '🤥', '😔', '😪', '🤤', '😴', '😷', '🤒', '🤕', '🤢',
                    '🤮', '🤧', '🥵', '🥶', '🥴', '😵', '🤯', '🤠', '🥳', '🥸',
                    '😎', '🤓', '🧐', '😕', '😟', '🙁', '☹️', '😮', '😯', '😲',
                    '😳', '🥺', '😦', '😧', '😨', '😰', '😥', '😢', '😭', '😱',
                    '😖', '😣', '😞', '😓', '😩', '😫', '🥱', '😤', '😡', '😠',
                    '🤬', '😈', '👿', '💀', '☠️', '💩', '🤡', '👹', '👺', '👻',
                    '👽', '👾', '🤖', '😺', '😸', '😹', '😻', '😼', '😽', '🙀',
                    '😿', '😾'
                )
            ),
            'people' => array(
                'label' => __( 'Menschen & Körper', 'psource-chat' ),
                'icon' => '👋',
                'emojis' => array(
                    '👋', '🤚', '🖐️', '✋', '🖖', '👌', '🤏', '✌️', '🤞', '🤟',
                    '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👍', '👎',
                    '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏',
                    '✍️', '💅', '🤳', '💪', '🦾', '🦿', '🦵', '🦶', '👂', '🦻',
                    '👃', '🧠', '🫀', '🫁', '🦷', '🦴', '👀', '👁️', '👅', '👄',
                    '💋', '🩸', '👶', '🧒', '👦', '👧', '🧑', '👱', '👨', '🧔',
                    '👩', '🧓', '👴', '👵', '🙍', '🙎', '🙅', '🙆', '💁', '🙋',
                    '🧏', '🙇', '🤦', '🤷', '👮', '🕵️', '💂', '🥷', '👷', '🤴',
                    '👸', '👳', '👲', '🧕', '🤵', '👰', '🤰', '🤱', '👼', '🎅',
                    '🤶', '🦸', '🦹', '🧙', '🧚', '🧛', '🧜', '🧝', '🧞', '🧟',
                    '💆', '💇', '🚶', '🧍', '🧎', '🏃', '🕺', '💃', '🕴️', '👯',
                    '🧖', '🧗', '🤺', '🏇', '⛷️', '🏂', '🏌️', '🏄', '🚣', '🏊',
                    '⛹️', '🏋️', '🚴', '🚵', '🤸', '🤼', '🤽', '🤾', '🤹', '🧘'
                )
            ),
            'animals' => array(
                'label' => __( 'Tiere & Natur', 'psource-chat' ),
                'icon' => '🐶',
                'emojis' => array(
                    '🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐻‍❄️', '🐨',
                    '🐯', '🦁', '🐮', '🐷', '🐽', '🐸', '🐵', '🙈', '🙉', '🙊',
                    '🐒', '🐔', '🐧', '🐦', '🐤', '🐣', '🐥', '🦆', '🦅', '🦉',
                    '🦇', '🐺', '🐗', '🐴', '🦄', '🐝', '🐛', '🦋', '🐌', '🐞',
                    '🐜', '🦟', '🦗', '🕷️', '🕸️', '🦂', '🐢', '🐍', '🦎', '🦖',
                    '🦕', '🐙', '🦑', '🦐', '🦞', '🦀', '🐡', '🐠', '🐟', '🐬',
                    '🐳', '🐋', '🦈', '🐊', '🐅', '🐆', '🦓', '🦍', '🦧', '🐘',
                    '🦛', '🦏', '🐪', '🐫', '🦒', '🦘', '🐃', '🐂', '🐄', '🐎',
                    '🐖', '🐏', '🐑', '🦙', '🐐', '🦌', '🐕', '🐩', '🦮', '🐕‍🦺',
                    '🐈', '🐈‍⬛', '🐓', '🦃', '🦚', '🦜', '🦢', '🦩', '🕊️', '🐇',
                    '🦝', '🦨', '🦡', '🦦', '🦥', '🐁', '🐀', '🐿️', '🦔'
                )
            ),
            'food' => array(
                'label' => __( 'Essen & Trinken', 'psource-chat' ),
                'icon' => '🍕',
                'emojis' => array(
                    '🍏', '🍎', '🍐', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🫐',
                    '🍈', '🍒', '🍑', '🥭', '🍍', '🥥', '🥝', '🍅', '🍆', '🥑',
                    '🥦', '🥬', '🥒', '🌶️', '🫑', '🌽', '🥕', '🫒', '🧄', '🧅',
                    '🥔', '🍠', '🥐', '🥯', '🍞', '🥖', '🥨', '🧀', '🥚', '🍳',
                    '🧈', '🥞', '🧇', '🥓', '🥩', '🍗', '🍖', '🦴', '🌭', '🍔',
                    '🍟', '🍕', '🫓', '🥙', '🌮', '🌯', '🫔', '🥗', '🥘', '🫕',
                    '🍝', '🍜', '🍲', '🍛', '🍣', '🍱', '🥟', '🦪', '🍤', '🍙',
                    '🍚', '🍘', '🍥', '🥠', '🥮', '🍢', '🍡', '🍧', '🍨', '🍦',
                    '🥧', '🧁', '🍰', '🎂', '🍮', '🍭', '🍬', '🍫', '🍿', '🍩',
                    '🍪', '🌰', '🥜', '🍯', '🥛', '🍼', '☕', '🫖', '🍵', '🧃',
                    '🥤', '🧋', '🍶', '🍺', '🍻', '🥂', '🍷', '🥃', '🍸', '🍹',
                    '🧊', '🥄', '🍴', '🍽️', '🥢', '🥡'
                )
            ),
            'activities' => array(
                'label' => __( 'Aktivitäten', 'psource-chat' ),
                'icon' => '⚽',
                'emojis' => array(
                    '⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱',
                    '🪀', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '🪃', '🥅', '⛳',
                    '🪁', '🏹', '🎣', '🤿', '🥊', '🥋', '🎽', '🛹', '🛷', '⛸️',
                    '🥌', '🎿', '⛷️', '🏂', '🪂', '🏋️‍♀️', '🏋️', '🏋️‍♂️', '🤼‍♀️', '🤼',
                    '🤼‍♂️', '🤸‍♀️', '🤸', '🤸‍♂️', '⛹️‍♀️', '⛹️', '⛹️‍♂️', '🤺', '🤾‍♀️', '🤾',
                    '🤾‍♂️', '🏌️‍♀️', '🏌️', '🏌️‍♂️', '🏇', '🧘‍♀️', '🧘', '🧘‍♂️', '🏄‍♀️', '🏄',
                    '🏄‍♂️', '🏊‍♀️', '🏊', '🏊‍♂️', '🤽‍♀️', '🤽', '🤽‍♂️', '🚣‍♀️', '🚣', '🚣‍♂️',
                    '🧗‍♀️', '🧗', '🧗‍♂️', '🚵‍♀️', '🚵', '🚵‍♂️', '🚴‍♀️', '🚴', '🚴‍♂️', '🏆',
                    '🥇', '🥈', '🥉', '🏅', '🎖️', '🏵️', '🎗️', '🎫', '🎟️', '🎪',
                    '🤹', '🤹‍♀️', '🤹‍♂️', '🎭', '🩰', '🎨', '🎬', '🎤', '🎧', '🎼',
                    '🎵', '🎶', '🥇', '🥈', '🥉', '🏆', '🏅', '🎖️', '🏵️', '🎗️'
                )
            ),
            'travel' => array(
                'label' => __( 'Reisen & Orte', 'psource-chat' ),
                'icon' => '🚗',
                'emojis' => array(
                    '🚗', '🚕', '🚙', '🚌', '🚎', '🏎️', '🚓', '🚑', '🚒', '🚐',
                    '🛻', '🚚', '🚛', '🚜', '🏍️', '🛵', '🚲', '🛴', '🛹', '🛼',
                    '🚁', '✈️', '🛩️', '🛫', '🛬', '🪂', '💺', '🚀', '🛸', '🚉',
                    '🚊', '🚝', '🚞', '🚋', '🚃', '🚋', '🚆', '🚄', '🚅', '🚈',
                    '🚂', '🚖', '🚘', '🚔', '🚍', '🚘', '🚖', '🚡', '🚠', '🚟',
                    '🎢', '🎡', '🎠', '🏗️', '🌁', '🗼', '🏭', '⛲', '🎑', '⛰️',
                    '🏔️', '🗻', '🌋', '🏕️', '🏖️', '🏜️', '🏝️', '🏞️', '🏟️', '🏛️',
                    '🏗️', '🧱', '🪨', '🪵', '🛖', '🏘️', '🏚️', '🏠', '🏡', '🏢',
                    '🏣', '🏤', '🏥', '🏦', '🏨', '🏩', '🏪', '🏫', '🏬', '🏭',
                    '🏯', '🏰', '🗼', '🗽', '⛪', '🕌', '🛕', '🕍', '⛩️', '🕋',
                    '⛺', '🌁', '🌃', '🏙️', '🌄', '🌅', '🌆', '🌇', '🌉', '♨️'
                )
            ),
            'objects' => array(
                'label' => __( 'Objekte', 'psource-chat' ),
                'icon' => '💎',
                'emojis' => array(
                    '⌚', '📱', '📲', '💻', '⌨️', '🖥️', '🖨️', '🖱️', '🖲️', '🕹️',
                    '🗜️', '💽', '💾', '💿', '📀', '📼', '📷', '📸', '📹', '🎥',
                    '📽️', '🎞️', '📞', '☎️', '📟', '📠', '📺', '📻', '🎙️', '🎚️',
                    '🎛️', '🧭', '⏱️', '⏲️', '⏰', '🕰️', '⌛', '⏳', '📡', '🔋',
                    '🔌', '💡', '🔦', '🕯️', '🪔', '🧯', '🛢️', '💸', '💵', '💴',
                    '💶', '💷', '💰', '💳', '💎', '⚖️', '🦯', '🧰', '🔧', '🔨',
                    '⛏️', '🛠️', '⚙️', '🔩', '⚗️', '🧪', '🧫', '🧬', '🔬', '🔭',
                    '📏', '📐', '📌', '📍', '📎', '🖇️', '📏', '📐', '✂️', '🗃️',
                    '🗄️', '🗑️', '🔒', '🔓', '🔏', '🔐', '🔑', '🗝️', '🔨', '🪓',
                    '⛏️', '⚒️', '🛠️', '🗡️', '⚔️', '🔫', '🪃', '🏹', '🛡️', '🪚',
                    '🔧', '🪛', '🔩', '⚙️', '🗜️', '⚖️', '🦯', '🔗', '⛓️', '🪝'
                )
            ),
            'symbols' => array(
                'label' => __( 'Symbole', 'psource-chat' ),
                'icon' => '❤️',
                'emojis' => array(
                    '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔',
                    '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️',
                    '✝️', '☪️', '🕉️', '☸️', '✡️', '🔯', '🕎', '☯️', '☦️', '🛐',
                    '⛎', '♈', '♉', '♊', '♋', '♌', '♍', '♎', '♏', '♐',
                    '♑', '♒', '♓', '🆔', '⚛️', '🉑', '☢️', '☣️', '📴', '📳',
                    '🈶', '🈚', '🈸', '🈺', '🈷️', '✴️', '🆚', '💮', '🉐', '㊙️',
                    '㊗️', '🈴', '🈵', '🈹', '🈲', '🅰️', '🅱️', '🆎', '🆑', '🅾️',
                    '🆘', '❌', '⭕', '🛑', '⛔', '📛', '🚫', '💯', '💢', '♨️',
                    '🚷', '🚯', '🚳', '🚱', '🔞', '📵', '🚭', '❗', '❕', '❓',
                    '❔', '‼️', '⁉️', '🔅', '🔆', '〽️', '⚠️', '🚸', '🔱', '⚜️',
                    '🔰', '♻️', '✅', '🈯', '💹', '❇️', '✳️', '❎', '🌐', '💠'
                )
            ),
            'flags' => array(
                'label' => __( 'Flaggen', 'psource-chat' ),
                'icon' => '🏁',
                'emojis' => array(
                    '🏁', '🚩', '🎌', '🏴', '🏳️', '🏳️‍🌈', '🏳️‍⚧️', '🏴‍☠️', '🇦🇫', '🇦🇽',
                    '🇦🇱', '🇩🇿', '🇦🇸', '🇦🇩', '🇦🇴', '🇦🇮', '🇦🇶', '🇦🇬', '🇦🇷', '🇦🇲',
                    '🇦🇼', '🇦🇺', '🇦🇹', '🇦🇿', '🇧🇸', '🇧🇭', '🇧🇩', '🇧🇧', '🇧🇾', '🇧🇪',
                    '🇧🇿', '🇧🇯', '🇧🇲', '🇧🇹', '🇧🇴', '🇧🇦', '🇧🇼', '🇧🇷', '🇮🇴', '🇻🇬',
                    '🇧🇳', '🇧🇬', '🇧🇫', '🇧🇮', '🇰🇭', '🇨🇲', '🇨🇦', '🇮🇨', '🇨🇻', '🇧🇶',
                    '🇰🇾', '🇨🇫', '🇹🇩', '🇨🇱', '🇨🇳', '🇨🇽', '🇨🇨', '🇨🇴', '🇰🇲', '🇨🇬',
                    '🇨🇩', '🇨🇰', '🇨🇷', '🇨🇮', '🇭🇷', '🇨🇺', '🇨🇼', '🇨🇾', '🇨🇿', '🇩🇰',
                    '🇩🇯', '🇩🇲', '🇩🇴', '🇪🇨', '🇪🇬', '🇸🇻', '🇬🇶', '🇪🇷', '🇪🇪', '🇸🇿',
                    '🇪🇹', '🇪🇺', '🇫🇰', '🇫🇴', '🇫🇯', '🇫🇮', '🇫🇷', '🇬🇫', '🇵🇫', '🇹🇫',
                    '🇬🇦', '🇬🇲', '🇬🇪', '🇩🇪', '🇬🇭', '🇬🇮', '🇬🇷', '🇬🇱', '🇬🇩', '🇬🇵'
                )
            )
        );
        
        // Allow filtering of emoji categories
        $this->emoji_categories = apply_filters( 'psource_chat_emoji_categories', $this->emoji_categories );
    }
    
    /**
     * Get all emoji categories
     * 
     * @return array
     */
    public function get_categories() {
        return $this->emoji_categories;
    }
    
    /**
     * Get emojis for a specific category
     * 
     * @param string $category_key
     * @return array|false
     */
    public function get_category_emojis( $category_key ) {
        if ( isset( $this->emoji_categories[ $category_key ] ) ) {
            return $this->emoji_categories[ $category_key ]['emojis'];
        }
        return false;
    }
    
    /**
     * Get all emojis as a flat array
     * 
     * @return array
     */
    public function get_all_emojis() {
        $all_emojis = array();
        foreach ( $this->emoji_categories as $category ) {
            $all_emojis = array_merge( $all_emojis, $category['emojis'] );
        }
        return $all_emojis;
    }
    
    /**
     * Generate the modern emoji picker HTML
     * 
     * @param array $chat_session
     * @return string
     */
    public function generate_emoji_picker( $chat_session = array() ) {
        if ( empty( $chat_session ) || $chat_session['box_emoticons'] !== 'enabled' ) {
            return '';
        }
        
        $content = '';
        $categories = $this->get_categories();
        
        if ( empty( $categories ) ) {
            return '';
        }
        
        // Get first emoji for the button
        $first_category = reset( $categories );
        $first_emoji = isset( $first_category['emojis'][0] ) ? $first_category['emojis'][0] : '😀';
        
        $content .= '<li class="psource-chat-send-input-emoticons">';
        $content .= '<a class="psource-chat-emoticons-menu" href="#" title="' . __( 'Emoji auswählen', 'psource-chat' ) . '">';
        $content .= '<span class="psource-chat-emoji-trigger">' . $first_emoji . '</span>';
        $content .= '</a>';
        
        // Modern emoji picker container
        $content .= '<div class="psource-chat-emoji-picker">';
        
        // Category tabs
        $content .= '<div class="psource-chat-emoji-categories">';
        $is_first = true;
        foreach ( $categories as $key => $category ) {
            $active_class = $is_first ? ' active' : '';
            $content .= '<button class="psource-chat-emoji-category-tab' . $active_class . '" data-category="' . esc_attr( $key ) . '" title="' . esc_attr( $category['label'] ) . '">';
            $content .= '<span class="psource-chat-emoji-category-icon">' . $category['icon'] . '</span>';
            $content .= '</button>';
            $is_first = false;
        }
        $content .= '</div>';
        
        // Emoji grid container
        $content .= '<div class="psource-chat-emoji-grid-container">';
        $is_first = true;
        foreach ( $categories as $key => $category ) {
            $active_class = $is_first ? ' active' : '';
            $content .= '<div class="psource-chat-emoji-grid' . $active_class . '" data-category="' . esc_attr( $key ) . '">';
            
            foreach ( $category['emojis'] as $emoji ) {
                $content .= '<button class="psource-chat-emoji-item" data-emoji="' . esc_attr( $emoji ) . '" title="' . esc_attr( $emoji ) . '">';
                $content .= $emoji;
                $content .= '</button>';
            }
            
            $content .= '</div>';
            $is_first = false;
        }
        $content .= '</div>';
        
        $content .= '</div>'; // .psource-chat-emoji-picker
        $content .= '</li>';
        
        return $content;
    }
    
    /**
     * Get emoji picker styles
     * 
     * @return string
     */
    public function get_emoji_picker_styles() {
        return "
        /* Modern Emoji Picker Styles */
        .psource-chat-emoji-picker {
            position: absolute;
            bottom: 100%;
            right: 0;
            width: 280px;
            max-height: 320px;
            background: #ffffff;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            display: none;
            z-index: 1000;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .psource-chat-emoji-picker.active {
            display: block;
        }
        
        .psource-chat-emoji-categories {
            display: flex;
            border-bottom: 1px solid #e1e1e1;
            background: #f8f9fa;
            border-radius: 8px 8px 0 0;
            padding: 4px;
            gap: 2px;
        }
        
        .psource-chat-emoji-category-tab {
            flex: 1;
            background: none;
            border: none;
            padding: 8px 4px;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.2s ease;
            font-size: 16px;
            line-height: 1;
        }
        
        .psource-chat-emoji-category-tab:hover {
            background: #e9ecef;
        }
        
        .psource-chat-emoji-category-tab.active {
            background: #007cba;
            color: white;
        }
        
        .psource-chat-emoji-category-icon {
            display: block;
        }
        
        .psource-chat-emoji-grid-container {
            max-height: 240px;
            overflow-y: auto;
            position: relative;
        }
        
        .psource-chat-emoji-grid {
            display: none;
            padding: 8px;
            grid-template-columns: repeat(8, 1fr);
            gap: 2px;
        }
        
        .psource-chat-emoji-grid.active {
            display: grid;
        }
        
        .psource-chat-emoji-item {
            background: none;
            border: none;
            padding: 6px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 18px;
            line-height: 1;
            transition: all 0.2s ease;
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .psource-chat-emoji-item:hover {
            background: #f0f0f0;
            transform: scale(1.1);
        }
        
        .psource-chat-emoji-item:active {
            transform: scale(0.95);
        }
        
        /* Custom scrollbar for emoji grid */
        .psource-chat-emoji-grid-container::-webkit-scrollbar {
            width: 6px;
        }
        
        .psource-chat-emoji-grid-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .psource-chat-emoji-grid-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .psource-chat-emoji-grid-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Emoji trigger button */
        .psource-chat-emoji-trigger {
            font-size: 16px;
            line-height: 1;
        }
        
        /* Position adjustment for emoji picker */
        .psource-chat-send-input-emoticons {
            position: relative;
        }
        
        /* Mobile responsive */
        @media (max-width: 480px) {
            .psource-chat-emoji-picker {
                width: 260px;
                max-height: 280px;
            }
            
            .psource-chat-emoji-grid {
                grid-template-columns: repeat(6, 1fr);
            }
            
            .psource-chat-emoji-item {
                font-size: 16px;
                padding: 4px;
            }
        }
        ";
    }
    
    /**
     * Get emoji picker JavaScript
     * 
     * @return string
     */
    public function get_emoji_picker_script() {
        return "
        // Modern Emoji Picker JavaScript
        (function($) {
            'use strict';
            
            // Emoji picker functionality
            function initEmojiPicker() {
                // Toggle emoji picker
                $(document).on('click', '.psource-chat-emoticons-menu', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var picker = $(this).siblings('.psource-chat-emoji-picker');
                    var wasVisible = picker.hasClass('active');
                    
                    // Close all other emoji pickers
                    $('.psource-chat-emoji-picker').removeClass('active');
                    
                    // Toggle current picker
                    if (!wasVisible) {
                        picker.addClass('active');
                    }
                });
                
                // Category tab switching
                $(document).on('click', '.psource-chat-emoji-category-tab', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var category = $(this).data('category');
                    var picker = $(this).closest('.psource-chat-emoji-picker');
                    
                    // Update active tab
                    picker.find('.psource-chat-emoji-category-tab').removeClass('active');
                    $(this).addClass('active');
                    
                    // Update active grid
                    picker.find('.psource-chat-emoji-grid').removeClass('active');
                    picker.find('.psource-chat-emoji-grid[data-category=\"' + category + '\"]').addClass('active');
                });
                
                // Emoji selection
                $(document).on('click', '.psource-chat-emoji-item', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var emoji = $(this).data('emoji');
                    
                    // Find the textarea within the same chat module
                    var chatModule = $(this).closest('.psource-chat-module-message-area');
                    var textarea = chatModule.find('textarea.psource-chat-send');
                    
                    if (textarea.length) {
                        // Insert emoji at cursor position
                        var currentText = textarea.val();
                        var cursorPos = textarea[0].selectionStart || currentText.length;
                        var newText = currentText.slice(0, cursorPos) + emoji + currentText.slice(cursorPos);
                        
                        textarea.val(newText);
                        
                        // Set cursor position after emoji
                        var newCursorPos = cursorPos + emoji.length;
                        if (textarea[0].setSelectionRange) {
                            textarea[0].setSelectionRange(newCursorPos, newCursorPos);
                        }
                        
                        // Focus textarea
                        textarea.focus();
                    } else {
                        // Fallback: try to find any visible textarea in the chat
                        var fallbackTextarea = $('textarea.psource-chat-send:visible').first();
                        if (fallbackTextarea.length) {
                            var currentText = fallbackTextarea.val();
                            var cursorPos = fallbackTextarea[0].selectionStart || currentText.length;
                            var newText = currentText.slice(0, cursorPos) + emoji + currentText.slice(cursorPos);
                            
                            fallbackTextarea.val(newText);
                            
                            var newCursorPos = cursorPos + emoji.length;
                            if (fallbackTextarea[0].setSelectionRange) {
                                fallbackTextarea[0].setSelectionRange(newCursorPos, newCursorPos);
                            }
                            
                            fallbackTextarea.focus();
                        }
                    }
                    
                    // Close emoji picker
                    $(this).closest('.psource-chat-emoji-picker').removeClass('active');
                });
                
                // Close emoji picker when clicking outside
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.psource-chat-send-input-emoticons').length) {
                        $('.psource-chat-emoji-picker').removeClass('active');
                    }
                });
                
                // Prevent emoji picker from closing when clicking inside
                $(document).on('click', '.psource-chat-emoji-picker', function(e) {
                    e.stopPropagation();
                });
            }
            
            // Initialize when document is ready
            $(document).ready(function() {
                initEmojiPicker();
            });
            
            // Re-initialize on AJAX updates
            $(document).on('psource_chat_content_updated', function() {
                initEmojiPicker();
            });
            
        })(jQuery);
        ";
    }
}
