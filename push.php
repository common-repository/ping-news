<?php
// One thing to consider is that we do not have control of the server environmental variables on
// the WordPress instances used by real users, this means that if we can't find a variable with
// `getenv` then we should set a defaults that uses the values we want to have in production

// NOTE: All constants and function names should be prefixed with `PINGNEWS` or `pingnews`,
// as applicable, as otherwise there could be conflicts with constants and functions in other
// plugins as all of these are defined globally in php

// Get vmn ingest url from environmental variable and if does not exist use default
$pingnews_vmn_ingest_url = getenv("VMN_INGEST_URL");
if (!$pingnews_vmn_ingest_url) {
    $pingnews_vmn_ingest_url = "https://ingest.pingnews.uk";
}
// Get vmn central url from environmental variable and if does not exist use default
$pingnews_vmn_central_url = getenv("VMN_CENTRAL_URL");
if (!$pingnews_vmn_central_url) {
    $pingnews_vmn_central_url = "https://app.pingnews.uk";
}
// This variable is used in `push.php` for JS error reporting
define("PINGNEWS_SENTRY_DSN", getenv("WP_SENTRY_DSN"));
define("PINGNEWS_VMN_INGEST_URL", $pingnews_vmn_ingest_url);
define("PINGNEWS_VMN_CENTRAL_URL", $pingnews_vmn_central_url);
// Constant for the capability required for pushing to vmn
define("PINGNEWS_PUSH_CAP", "push_to_ping");
// Constants for user entering authorization token
define("PINGNEWS_AUTH_TOKEN", "pingnews_auth_token");
define("PINGNEWS_AUTH_TOKEN_SETTINGS", "pingnews_auth_token_settings");
define("PINGNEWS_SETTINGS", "ping-settings");
// The interval for which package data from Ingest to polled (in milliseconds)
define("PINGNEWS_POLL_INTERVAL", "120000");
// Constant for the key to store package token in a posts meta data
define("PINGNEWS_PACKAGE_DATA", "pingnews_package_data");
// Constant for taxonomy select tags
define("PINGNEWS_LOCATION", "pingnews_location");
define("PINGNEWS_STORY_FOCUS", "pingnews_story_focus");
// Constant for outer div around the package token html
define("PINGNEWS_PACKAGE_DATA_OUTER", "pingnews_package_data_outer");
// Constants for spans to display package data
define("PINGNEWS_PACKAGE_TOKEN", "pingnews_package_token");
define("PINGNEWS_PACKAGE_DATE_CREATED", "pingnews_date_created");
define("PINGNEWS_PACKAGE_ACCEPTANCE_STATE", "pingnews_package_acceptance_state");
define("PINGNEWS_PACKAGE_FEEDBACK", "pingnews_package_feedback");
define("PINGNEWS_PACKAGE_FEEDBACK_OUTER", "pingnews_package_feedback_outer");
define("PINGNEWS_PACKAGE_TAGS", "pingnews_package_tags");
// Constant for push to ping button anchor
define("PINGNEWS_PUSH_TO_PING_ANCHOR", "pingnews_push_to_ping_anchor");
// Boolean for whether Trends features are applied
// NOTE: We would usually set this from ansible but we do not have control of environmental
// variables on external WordPress instances
define("PINGNEWS_TRENDS_FEATURE_FLAG", true );
define("PINGNEWS_EXISTING_TAGS", "pingnews_existing_tags");

// require_once(__DIR__.'/../../../wp-includes/post-thumbnail-template.php');

/**
 * Define a custom role for pushing to VMN
 *
 * @return void
 */
function pingnews_pusher_role() {
    add_role(
        "pingnews_pusher",
        "Pusher",
        [
            PINGNEWS_PUSH_CAP => true,
        ]
    );

    // Add the capability to the administrator role as well
    $admin_role = get_role("administrator");
    $admin_role->add_cap(PINGNEWS_PUSH_CAP, true);
}

/**
 * Function which returns whether the current user has the push to ping capability
 *
 * @return Boolean
 */
function pingnews_has_push_caps() {
    $current_user = wp_get_current_user();
    return user_can($current_user, PINGNEWS_PUSH_CAP);
}

/**
 * Renders the HTML page for the Ping News push interface
 *
 * @return void
 */
function pingnews_settings_page_html() {
    if ( !pingnews_has_push_caps() ) {
        return;
    } ?>

    <style type="text/css">
        .vmn_ping__ping_logo {
            display: block;
            margin: 3em 0 1em;
            width: 100%;
            max-width: 12em;
        }
        .vmn_ping__form_component,
        .vmn_ping__form_component__label,
        .vmn_ping__form_component__text {
            box-sizing: border-box;
        }
        .vmn_ping__form_component {
            border-left: 3px solid hsla(163, 100%, 50%, .5);
            border-radius: 3px 0 0 3px;
            width: 100%;
            padding-left: .5em;
        }
        * + .vmn_ping__form_component {
            margin-top: 2em;
        }
        .vmn_ping__form_component__label {
            display: block;
            font-weight: 600;
            font-size: 14px;
        }
        .vmn_ping__form_component__text[type="text"] {
            -webkit-box-shadow: none;
            box-shadow: none;
            width: 40ch;
            max-width: 100%;
            border: 1px solid hsla(263, 90%, 50%, 1);
            border-radius: 3px;
            background-color: white;
            padding: 1em .5em;
        }
        .vmn_ping__form_component__text[type="text"]:focus {
            outline: 0;
            border: 1px solid hsla(263, 90%, 50%, 1);
            -webkit-box-shadow: 0 0 6px hsla(263, 90%, 50%, .2);
            box-shadow: 0 0 6px hsla(263, 90%, 50%, .2);
        }
        .vmn_ping__form_component__label + .vmn_ping__form_component__text {
            margin-top: .8em;
        }
        .button.button-primary {
            will-change: color, background-color, border-color, box-shadow, text-shadow, transform;
            -webkit-transition-property: color, background-color, border-color, text-shadow, -webkit-box-shadow, -webkit-transform;
            transition-property: color, background-color, border-color, text-shadow, -webkit-box-shadow, -webkit-transform;
            transition-property: color, background-color, border-color, box-shadow, text-shadow, transform;
            transition-property: color, background-color, border-color, box-shadow, text-shadow, transform, -webkit-box-shadow, -webkit-transform;
            -webkit-transition-duration: 0.14s;
            transition-duration: 0.14s;
            -webkit-transition-timing-function: cubic-bezier(0.25, 0.25, 0.75, 0.75);
            transition-timing-function: cubic-bezier(0.25, 0.25, 0.75, 0.75);
            cursor: pointer;
            display: inline-block;
            box-shadow: inset 0 -2px 0 hsla(265, 90%, 38%, 1);
            border-radius: 3px;
            border: 1px solid hsla(265, 90%, 38%, 1);
            background-color: hsla(256, 90%, 62%, 1);
            padding: .74em 1em .9em;
            word-break: keep-all;
            text-shadow: 0 0 .24em hsla(265, 90%, 38%, 1);
            text-decoration: none;
            color: white;
        }
        .button.button-primary:active,
        .button.button-primary:focus,
        .button.button-primary:hover {
            box-shadow: inset 0 -1px 0 hsla(265, 90%, 38%, 1);
            background-color: hsla(263, 90%, 50%, 1);
            color: white;
        }
        * + .button.button-primary {
            margin-top: 1em;
        }
    </style>
    <div class="wrap">
        <img
        class="vmn_ping__ping_logo"
        src="<?php echo plugin_dir_url( __FILE__ ) . "images/ping_logo.png"; ?>">
        <h1>Settings for Ping!</h1>
        <p>Please enter your authorization token below.</p>
        <p>You can get this by signing in to <a target="_blank" href="<?php echo esc_html(PINGNEWS_VMN_CENTRAL_URL);?>">Ping!</a> and clicking <a target="_blank" href="<?php echo esc_html(PINGNEWS_VMN_CENTRAL_URL . "/user-settings");?>">View User Settings</a>.</p>
        <form
        action="options.php"
        method="post">
            <?php settings_fields( PINGNEWS_AUTH_TOKEN_SETTINGS ); ?>
            <?php do_settings_sections( PINGNEWS_AUTH_TOKEN_SETTINGS ); ?>
            <div class="vmn_ping__form_component">
                <label
                class="vmn_ping__form_component__label"
                for="<?php echo esc_attr(PINGNEWS_AUTH_TOKEN); ?>">Authorization token:</label>
                <input
                class="vmn_ping__form_component__text"
                name="<?php echo esc_attr(PINGNEWS_AUTH_TOKEN); ?>"
                type="text"
                value="<?php echo esc_attr(get_option( PINGNEWS_AUTH_TOKEN )); ?>">
            </div><!-- .vmn_ping__form_component -->
            <input
            class="button button-primary"
            id="submit"
            name="submit"
            style="margin-top: 2rem;"
            type="submit"
            value="Save Changes">
            <script type="text/javascript">
                document.getElementById("submit").addEventListener("click", function(){
                    document.getElementById("submit").value = "Saved!";
                });
            </script>
        </form>
    </div>
    <?php
}

/**
 * Saves `PINGNEWS_AUTH_TOKEN` to Ping News settings
 *
 * @return void
 */
function pingnews_update_auth_token() {
  register_setting(PINGNEWS_AUTH_TOKEN_SETTINGS, PINGNEWS_AUTH_TOKEN);
}

/**
 * Adds the menu button if the user has push capability
 *
 * @return void
 */
function pingnews_menu_page() {
    add_menu_page(
        "Ping News Push",
        "Ping!",
        PINGNEWS_PUSH_CAP,
        PINGNEWS_SETTINGS,
        "pingnews_settings_page_html"
    );
}

/**
 * The html to render in metabox and send AJAX request on clicking button
 *
 * @return void
 */
function pingnews_metabox_html() {
    // Add thickbox for claiming access rights to photos
    add_thickbox();

    $post_id = get_the_id();
    $package_data = False;
    if ( $post_id ) {
        $package_data = end(get_post_meta($post_id, PINGNEWS_PACKAGE_DATA));
    }

    // TODO: When it comes to applying css use class toggling rather than inline styles
    $display = "none";
    $display_feedback = "none";
    if ( $package_data ) {
        $display = "inline";
        $package_json = json_decode($package_data);
        $date_created = new DateTime($package_json->date_created, new DateTimeZone("UTC"));
        if ( $package_json->feedback ) {
            $display_feedback = "inline";
        }
    }

    try {
        $taxonomies = pingnews_get_taxonomies();
    // If call to get taxonomies fails ask for user to refresh
    } catch (Exception $e) {
        echo $e->getMessage();
        return;
    }

    if ( PINGNEWS_TRENDS_FEATURE_FLAG ) {
        $existing_tags = pingnews_get_existing_tags();
    }

    ?>
    <style type="text/css">
        .vmn_ping__ping_logo {
            display: block;
            margin: 3em 0 1em;
            width: 100%;
            max-width: 12em;
        }
        .vmn_ping__form_component,
        .vmn_ping__form_component__label,
        .vmn_ping__form_component__text {
            box-sizing: border-box;
        }
        .vmn_ping__form_component {
            border-left: 3px solid hsla(163, 100%, 50%, .5);
            border-radius: 3px 0 0 3px;
            width: 100%;
            padding-left: .5em;
        }
        * + .vmn_ping__form_component {
            margin-top: 2em;
        }
        .vmn_ping__form_component__label {
            display: block;
            font-weight: 600;
            font-size: 14px;
        }
        .wp-admin .vmn_ping__form_component__select {
            cursor: pointer;
            appearance: none;
            width: 100%;
            box-shadow: inset 0 -3px 0 hsla(256, 100%, 94%, 1);
            border-radius: 3px;
            border: 1px solid hsla(263, 90%, 50%, 1);
            border-left-width: 3px;
            background-color: white;
            padding: 1em .5em;
            line-height: 1.5em;
            height: auto;
        }
        .wp-admin .vmn_ping__form_component__select:focus {
            outline: 0;
            border: 1px solid hsla(263, 90%, 50%, 1);
            -webkit-box-shadow: 0 0 6px hsla(263, 90%, 50%, .2);
            box-shadow: 0 0 6px hsla(263, 90%, 50%, .2);
        }
        .vmn_ping__form_component__label + .vmn_ping__form_component__select {
            margin-top: .8em;
        }
        .vmn_ping__definitions__list {
            display: block;
            width: 100%;
        }
        * + .vmn_ping__definitions__list {
            margin-top: 2em;
        }
        .vmn_ping__definitions__list--hidden {
            display: none;
        }
        .vmn_ping__definitions__term {
            font-weight: 600;
            display: block;
        }
        * + .vmn_ping__definitions__term {
            margin-top: 2em;
        }
        .vmn_ping__definitions__description {
            display: block;
            margin: 0;
            margin-bottom: 0;
            margin-left: 2ch;
            padding: 0;
            font-style: italic;
        }
        * + .vmn_ping__definitions__description {
            margin-top: .5em;
        }
        .vmn_ping__button_container * + .vmn_ping__button {
            margin-top: 0;
        }
        .vmn_ping__button_container .vmn_ping__button {
            margin-bottom: 1em;
        }
        .vmn_ping__button_container .vmn_ping__button:not(:last-child) {
            margin-right: 1em;
        }
        .vmn_ping__button_container {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
            flex-wrap: wrap;
            margin-bottom: -1em;
            width: 100%;
        }
        * + .vmn_ping__button_container {
            margin-top: 1.6em;
        }
        .vmn_ping__button {
            will-change: color, background-color, border-color, box-shadow, text-shadow, transform;
            -webkit-transition-property: color, background-color, border-color, text-shadow, -webkit-box-shadow, -webkit-transform;
            transition-property: color, background-color, border-color, text-shadow, -webkit-box-shadow, -webkit-transform;
            transition-property: color, background-color, border-color, box-shadow, text-shadow, transform;
            transition-property: color, background-color, border-color, box-shadow, text-shadow, transform, -webkit-box-shadow, -webkit-transform;
            -webkit-transition-duration: 0.14s;
            transition-duration: 0.14s;
            -webkit-transition-timing-function: cubic-bezier(0.25, 0.25, 0.75, 0.75);
            transition-timing-function: cubic-bezier(0.25, 0.25, 0.75, 0.75);
            cursor: pointer;
            display: inline-block;
            box-shadow: inset 0 -2px 0 hsla(265, 90%, 38%, 1);
            border-radius: 3px;
            border: 1px solid hsla(265, 90%, 38%, 1);
            background-color: hsla(256, 90%, 62%, 1);
            padding: .74em 1em .9em;
            word-break: keep-all;
            text-shadow: 0 0 .24em hsla(265, 90%, 38%, 1);
            text-decoration: none;
            color: white;
        }
        .vmn_ping__button:active,
        .vmn_ping__button:focus,
        .vmn_ping__button:hover {
            box-shadow: inset 0 -1px 0 hsla(265, 90%, 38%, 1);
            background-color: hsla(263, 90%, 50%, 1);
            color: white;
        }
        * + .vmn_ping__button {
            margin-top: 1em;
        }
        .vmn_ping__button.vmn_ping__button--reductive_action {
            -webkit-box-shadow: inset 0 -3px 0 #ffebec;
            box-shadow: inset 0 -3px 0 #ffebec;
            border-color: #fb0e16;
            background-color: #fff;
            text-shadow: none;
            color: #fb0e16;
        }
        .vmn_ping__button.vmn_ping__button--reductive_action:active,
        .vmn_ping__button.vmn_ping__button--reductive_action:focus,
        .vmn_ping__button.vmn_ping__button--reductive_action:hover {
            -webkit-box-shadow: inset 0 -1px 0 #ffebec;
            box-shadow: inset 0 -1px 0 #ffebec;
            border-color: #d3030a;
            color: #d3030a;
        }
        #TB_ajaxContent p:first-of-type {
            margin-top: 1.6em;
        }
        .chosen-container {
            position: relative;
            z-index: 10;
            display: inline-block;
            vertical-align: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            padding-bottom: 2px;
        }
        * + .chosen-container {
            margin-top: .8em;
        }
        .chosen-container .chosen-drop {
            clip: rect(0, 0, 0, 0);
            -webkit-clip-path: inset(100% 100%);
            clip-path: inset(100% 100%);
            position: absolute;
            z-index: auto;
            top: 100%;
            left: 0;
            border: 1px solid #650df2;
            border-radius: 0 0 3px 3px;
            width: 100%;
            background-color: #fff;
        }
        .chosen-container.chosen-with-drop .chosen-drop {
            clip: auto;
            -webkit-clip-path: none;
            clip-path: none;
        }
        .chosen-container .chosen-results {
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
            max-height: 14rem;
            background-color: #fff;
        }
        .chosen-container .chosen-results li {
            will-change: background-color, color;
            -webkit-transition-property: background-color, color;
            transition-property: background-color, color;
            -webkit-transition-duration: 0.14s;
            transition-duration: 0.14s;
            -webkit-transition-timing-function: cubic-bezier(0.25, 0.25, 0.75, 0.75);
            transition-timing-function: cubic-bezier(0.25, 0.25, 0.75, 0.75);
            display: none;
            word-wrap: break-word;
            padding: 0.539333333333333rem 2.427rem 0.539333333333333rem 0.6472rem;
        }
        .chosen-container .chosen-results li.active-result,.chosen-container .chosen-results li.disabled-result,.chosen-container .chosen-results li.no-results {
            display: list-item;
        }
        .chosen-container .chosen-results li.disabled-result {
            color: #cccace;
        }
        .chosen-container .chosen-results li.no-results {
            color: #fc4046;
        }
        .chosen-container .chosen-results li.active-result {
            cursor: pointer;
        }
        .chosen-container .chosen-results li.disabled-result {
            cursor: default;
             color: #e5e4e7;
        }
        .chosen-container .chosen-results li.highlighted {
            background-color: #e9e0ff;
            color: #19191a;
        }
        .chosen-container-multi .chosen-choices-outer {
            overflow-x: hidden;
            overflow-y: auto;
            height: 100%;
        }
        .chosen-container-multi .chosen-choices {
            cursor: text;
            position: relative;
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-orient: horizontal;
            -webkit-box-direction: normal;
            -ms-flex-direction: row;
            flex-direction: row;
            -ms-flex-wrap: wrap;
            flex-wrap: wrap;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            -webkit-box-pack: start;
            -ms-flex-pack: start;
            justify-content: flex-start;
            margin: 0;
            width: 100%;
            border-radius: 3px 3px 0 0;
            background-color: #fff;
            padding: 1em;
        }
        .chosen-container-multi .chosen-choices li.search-field:not(:first-child),
        .chosen-container-multi .chosen-choices li.search-field:not(:first-child) input {
            -webkit-box-flex: 0;
            -ms-flex: 0 0 1px;
            flex: 0 0 1px;
        }
        .chosen-container-multi .chosen-choices li.search-field {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            margin: 0.3236rem 0 0;
            padding: 0;
            white-space: nowrap;
        }
        .chosen-container-multi .chosen-choices li.search-field input {
            padding: 0;
        }
        .chosen-container-multi .chosen-choices li.search-choice {
            cursor: default;
            -webkit-box-flex: 0;
            -ms-flex: 0 0 auto;
            flex: 0 0 auto;
            position: relative;
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            -webkit-box-pack: center;
            -ms-flex-pack: center;
            justify-content: center;
            margin: 0.3236rem 0.3236rem 0 0;
            border: 1px solid #00ffb7;
            max-width: 100%;
            background-color: #fff;
            padding: .25rem .5em .25rem .375rem;
            line-height: 1.4;
            font-size: 14px;
        }
        .chosen-container-multi .chosen-choices li.search-choice span {
            display: inline-block;
            word-wrap: break-word;
        }
        .chosen-container-multi .chosen-choices li.search-choice .search-choice-close {
            display: inline-block;
            margin-left: 0.3236rem;
            height: 1.25em;
            width: 0.539333333333333rem;
            background-image: url("<?php echo plugin_dir_url( __FILE__ ) . "images/chosen_cross.svg"; ?>");
            background-position: center;
            background-repeat: no-repeat;
            background-size: contain;
        }
        .edit-post-sidebar input.chosen-search-input[type="text"] {
            box-shadow: none;
            border: 0;
            background-color: transparent;
        }
        .chosen-container-multi .chosen-results {
            margin: 0;
            padding: 0;
        }
        .chosen-container-multi .chosen-drop .result-selected {
            display: list-item;
            cursor: default;
            color: #b2afb6;
        }
        .chosen-container {
            will-change: border-color, box-shadow;
            -webkit-transition-property: border-color, -webkit-box-shadow;
            transition-property: border-color, -webkit-box-shadow;
            transition-property: border-color, box-shadow;
            transition-property: border-color, box-shadow, -webkit-box-shadow;
            -webkit-transition-duration: 0.14s;
            transition-duration: 0.14s;
            -webkit-transition-timing-function: cubic-bezier(0.25, 0.25, 0.75, 0.75);
            transition-timing-function: cubic-bezier(0.25, 0.25, 0.75, 0.75);
            cursor: pointer;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            width: 100%;
            border-radius: 3px;
            border: 1px solid #650df2;
            border-left-width: 3px;
            background-color: #fff;
            background-position: right 0.809rem top 50%;
            background-repeat: no-repeat;
            background-size: 1.2135rem;
            color: #3a393c;
        }
        .chosen-container:active,
        .chosen-container:focus,
        .vmn_ping__form_component__select:hover,
        .chosen-container:hover {
            outline: 0;
            -webkit-box-shadow: inset 0 -1px 0 #e9e0ff;
            box-shadow: inset 0 -1px 0 #e9e0ff;
            border-color: #520ab8;
        }
        .chosen-container {
            -webkit-box-shadow: inset 0 -2px 0 #e9e0ff;
            box-shadow: inset 0 -2px 0 #e9e0ff;
        }
        .vmn_ping__form_options .vmn_ping__form_component .vmn_ping__form_component__text {
            will-change: border-color, box-shadow;
            -webkit-transition-property: border-color, -webkit-box-shadow;
            transition-property: border-color, -webkit-box-shadow;
            transition-property: border-color, box-shadow;
            transition-property: border-color, box-shadow, -webkit-box-shadow;
            -webkit-transition-duration: 0.14s;
            transition-duration: 0.14s;
            -webkit-transition-timing-function: cubic-bezier(0.25, 0.25, 0.75, 0.75);
            transition-timing-function: cubic-bezier(0.25, 0.25, 0.75, 0.75);
            width: 100%;
            max-width: 100%;
            -webkit-box-shadow: inset 0 -3px 0 #e9e0ff;
            box-shadow: inset 0 -3px 0 #e9e0ff;
            border-radius: 3px;
            border: 1px solid #650df2;
            border-left-width: 3px;
            background-color: #fff;
            padding: 1em;
            line-height: 1.5em;
            font-size: 14px;
        }
        .vmn_ping__form_options .vmn_ping__form_component .vmn_ping__form_component__text::-webkit-input-placeholder {
            color: #b2afb6;
            opacity: 1;
        }
        .vmn_ping__form_options .vmn_ping__form_component .vmn_ping__form_component__text:-moz-placeholder {
            color: #b2afb6;
            opacity: 1;
        }
        .vmn_ping__form_options .vmn_ping__form_component .vmn_ping__form_component__text::-moz-placeholder {
            color: #b2afb6;
            opacity: 1;
        }
        .vmn_ping__form_options .vmn_ping__form_component .vmn_ping__form_component__text:-ms-input-placeholder {
            color: #b2afb6;
            opacity: 1;
        }
        .vmn_ping__form_options .vmn_ping__form_component .vmn_ping__form_component__text:active,
        .vmn_ping__form_options .vmn_ping__form_component .vmn_ping__form_component__text:focus,
        .vmn_ping__form_options .vmn_ping__form_component .vmn_ping__form_component__text:hover {
            outline: 0;
            -webkit-box-shadow: inset 0 -1px 0 #e9e0ff;
            box-shadow: inset 0 -1px 0 #e9e0ff;
            border-color: #520ab8;
        }
        label + .vmn_ping__form_component__text,
        * + .vmn_ping__form_component__help_text {
            margin-top: .8em;
        }
        .vmn_ping__form_component__help_text {
            display: block;
        }
    </style>
    <div class="vmn_ping__form_options">
        <div
        id="pingnews-thickbox"
        style="display:none;">
            <img
            class="vmn_ping__ping_logo"
            src="<?php echo plugin_dir_url( __FILE__ ) . "images/ping_logo.png"; ?>">
            <p>You are about to push your story to Ping!</p>
            <p>Please make sure you have saved your story as a draft - or published it - before you push it to us.</p>
            <p>Please cancel if you do not own, or do not have copyright approval over the access rights to all media, especially imagery, in your story.</p>
            <p>For more on copyright, follow this link:</p>
            <a href="https://www.communityjournalism.co.uk/media-law-guidance/" target="_blank">https://www.communityjournalism.co.uk/media-law-guidance/</a>
            <p>Choose OK to continue.</p>
            <div class="vmn_ping__button_container">
                <!-- Do not remove `type="button"` as otherwise this will default to `submit` and it will perform the same action as clicking publish/update -->
                <button
                class="vmn_ping__button vmn_ping__button--reductive_action"
                onclick="tb_remove();"
                type="button">Cancel</button>
                <button
                class="vmn_ping__button"
                id="confirmPost"
                onclick="postPackage();"
                type="button">OK</button>
            </div><!-- .vmn_ping__button_container -->
        </div>
        <?php if ( PINGNEWS_TRENDS_FEATURE_FLAG ) : ?>
            <div class="vmn_ping__form_component">
                <label class="vmn_ping__form_component__label">Tags:</label>
                <?php if ( $existing_tags ) : ?>
                    <select
                    class="chosen-select vmn_ping__form_component__select"
                    id="<?php echo PINGNEWS_EXISTING_TAGS; ?>"
                    multiple>
                        <?php pingnews_get_tag_options($existing_tags->results, $package_json->tags); ?>
                    </select><!-- .vmn_ping__form_component__select -->
                    <span class="vmn_ping__form_component__help_text">Add up to <?php echo $taxonomies->tag_limit; ?> tags to your story</span>
                <?php else : ?>
                    <p>Sorry, we were unable to find existing tags in the Ping! system.</p>
                <?php endif; ?>
            </div><!-- .vmn_ping__form_component -->
            <div class="vmn_ping__form_component">
                <label class="vmn_ping__form_component__label">Create new tags</label>
                <input
                class="vmn_ping__form_component__text"
                id="<?php echo esc_attr(PINGNEWS_PACKAGE_TAGS); ?>"
                name="tags"
                type="text"
                value=""
                placeholder="comma-separated list of tags">
                <span class="vmn_ping__form_component__help_text">If you can't find existing tags above, please add them here, separated by commas (these are included in your limit of <?php echo $taxonomies->tag_limit; ?> tags).</span>
            </div><!-- .vmn_ping__form_component -->
        <?php endif; ?>
        <div class="vmn_ping__form_component">
            <label class="vmn_ping__form_component__label">Location:</label>
            <select
            class="vmn_ping__form_component__select"
            id="<?php echo PINGNEWS_LOCATION; ?>">
                <?php pingnews_get_taxonomy_options($taxonomies->location, $package_json->package_data->meta->location); ?>
            </select><!-- .vmn_ping__form_component__select -->
        </div><!-- .vmn_ping__form_component -->
        <div class="vmn_ping__form_component">
            <label class="vmn_ping__form_component__label">Story focus:</label>
            <select
            class="vmn_ping__form_component__select"
            id="<?php echo PINGNEWS_STORY_FOCUS; ?>">
                <?php
                pingnews_get_taxonomy_options($taxonomies->story_focus, $package_json->package_data->meta->story_focus); ?>
            </select><!-- .vmn_ping__form_component__select -->
        </div><!-- .vmn_ping__form_component -->
        <dl
        class="vmn_ping__definitions__list<?php if ( !$package_json && !$date_created ) : ?>--hidden<?php endif; ?>"
        id="<?php echo PINGNEWS_PACKAGE_DATA_OUTER; ?>">
            <dt class="vmn_ping__definitions__term">Story ID:</dt>
            <dd
            class="vmn_ping__definitions__description"
            id="<?php echo PINGNEWS_PACKAGE_TOKEN; ?>"><?php if ( $package_json ) { echo esc_html($package_json->package_token); } ?></dd>
            <dt class="vmn_ping__definitions__term">Date last pushed:</dt>
            <dd
            class="vmn_ping__definitions__description"
            id="<?php echo PINGNEWS_PACKAGE_DATE_CREATED; ?>"><?php if ( $date_created ) { echo esc_html($date_created->format("g:ia (T), jS F Y")); } ?></dd>
            <dt class="vmn_ping__definitions__term">Acceptance state:</dt>
            <dd
            class="vmn_ping__definitions__description"
            id="<?php echo PINGNEWS_PACKAGE_ACCEPTANCE_STATE; ?>"><?php if ( $package_json ) { echo esc_html($package_json->acceptance_state); } ?></dd>
            <div
            id="<?php echo PINGNEWS_PACKAGE_FEEDBACK_OUTER; ?>"
            style="display: <?php echo esc_attr($display_feedback); ?>">
                <dt class="vmn_ping__definitions__term">Feedback:</dt>
                <dd
                class="vmn_ping__definitions__description"
                id="<?php echo PINGNEWS_PACKAGE_FEEDBACK; ?>"><?php if ( $package_json ) { echo esc_html($package_json->feedback); } ?></dd>
            </div>
        </dl>
    </div><!-- .vmn_ping__form_options -->
    <a
    id="<?php echo PINGNEWS_PUSH_TO_PING_ANCHOR; ?>"
    class="vmn_ping__button thickbox"
    href="#TB_inline?width=600&height=550&inlineId=pingnews-thickbox">
        <?php if ( $package_json ): ?>Push again!<?php else: ?>Push to Ping!<?php endif; ?>
    </a>
    <script type="text/javascript">
        const pushAnchor = document.getElementById("<?php echo PINGNEWS_PUSH_TO_PING_ANCHOR; ?>")
        const locationSelect = document.getElementById("<?php echo PINGNEWS_LOCATION; ?>")
        const storyFocusSelect = document.getElementById("<?php echo PINGNEWS_STORY_FOCUS; ?>")
        const existingTagsSelect = document.getElementById("<?php echo PINGNEWS_EXISTING_TAGS; ?>")
        const newTagsInput = document.getElementById("<?php echo esc_attr(PINGNEWS_PACKAGE_TAGS); ?>")

        /**
         * Toggles the state of the push anchor based on the values of locationSelect and storyFocusSelect.
         * If either of the values is falsy, the anchor is disabled. Otherwise, it is enabled.
         */
        let togglePushAnchor = () => {

            /**
             * Disables the push anchor by adding necessary CSS classes and modifying attributes.
             */
            let disableAnchor = () => {
                pushAnchor.classList.add("vmn_ping__button--reductive_action")
                pushAnchor.classList.remove("thickbox")
                pushAnchor.textContent = "Please add tags, location, and story focus"
                pushAnchor.href = "#"
            }

            /**
             * Enables the push anchor by removing CSS classes and modifying attributes.
             */
            let enableAnchor = () => {
                pushAnchor.classList.remove("vmn_ping__button--reductive_action")
                pushAnchor.classList.add("thickbox")
                pushAnchor.textContent = "Push to Ping!"
                pushAnchor.href = "#TB_inline?width=600&height=550&inlineId=pingnews-thickbox"
            }

            if (!locationSelect.value | !storyFocusSelect.value) {
                disableAnchor()
            } else if (!existingTagsSelect.value & !newTagsInput.value){
                disableAnchor()
            }else {
                enableAnchor()
            }
        }

        togglePushAnchor()
        locationSelect.addEventListener('change', () => togglePushAnchor())
        storyFocusSelect.addEventListener('change', () => togglePushAnchor())
        existingTagsSelect.parentElement.parentElement.addEventListener('click', () => {
            togglePushAnchor()
            // when a selection is made, elems with this class are created
            // so we add an event listener to each here
            document.querySelectorAll('.search-choice-close').forEach((elem) => {
                elem.addEventListener('click', () => togglePushAnchor())
            })
        }) 
        newTagsInput.addEventListener('change', () => togglePushAnchor())
    </script>

    <?php if ( PINGNEWS_SENTRY_DSN ) : ?>
        <script src="https://browser.sentry-cdn.com/5.11.1/bundle.min.js" integrity="sha384-r7/ZcDRYpWjCNXLUKk3iuyyyEcDJ+o+3M5CqXP5GUGODYbolXewNHAZLYSJ3ZHcV" crossorigin="anonymous"></script>
    <?php endif; ?>

    <?php if ( PINGNEWS_TRENDS_FEATURE_FLAG ) : ?>
        <script src="https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.jquery.min.js"></script>
        <script>jQuery(".chosen-select").chosen({width: "100%"});</script>
    <?php endif; ?>

    <script type="text/javascript">
        var canPost = true;
        <?php if ( PINGNEWS_SENTRY_DSN ) : ?>
            // Initialise Sentry
            Sentry.init({ dsn: "<?php echo PINGNEWS_SENTRY_DSN; ?>" });
        <?php endif; ?>
        // This function updates information in the metabox that might change over time
        function updateDynamicInfo(package_data, update_tags) {
            if ( package_data["acceptance_state"] ) {
                document.getElementById("<?php echo PINGNEWS_PACKAGE_ACCEPTANCE_STATE; ?>").innerHTML = package_data["acceptance_state"];
                document.getElementById("<?php echo PINGNEWS_PUSH_TO_PING_ANCHOR; ?>").innerHTML = "Push again!";
            }

            feedback_outer_element = document.getElementById("<?php echo PINGNEWS_PACKAGE_FEEDBACK_OUTER; ?>");
            if ( package_data["feedback"] ) {
                feedback_outer_element.style.display = "inline";
                document.getElementById("<?php echo PINGNEWS_PACKAGE_FEEDBACK; ?>").innerHTML = package_data["feedback"];
            } else {
                feedback_outer_element.style.display = "none";
            }

            if (<?php echo PINGNEWS_TRENDS_FEATURE_FLAG; ?>) {
                tags_element = document.getElementById("<?php echo PINGNEWS_PACKAGE_TAGS; ?>")
                let tags = package_data["approved_tags"] || package_data["tags"];
                if ( tags && update_tags) {
                    let existing_tags_element = document.getElementById("<?php echo PINGNEWS_EXISTING_TAGS; ?>");

                    if (existing_tags_element) {
                        tags = tags.split(", ");
                        let existing_tags = [];

                        // Remove items from tags array if they appear in existing_tags array
                        for (let i = 0; i < existing_tags_element.options.length; i++) {
                            const index = tags.indexOf(existing_tags_element.options[i].value);
                            if (index > -1) {
                                tags.splice(index, 1);
                                // Dynamically select items in chosen multi-select element
                                existing_tags_element.options[i].selected = true;
                            }
                        }

                        // Populate contents of tags text area
                        tags_element.value = tags.join(", ");
                        // Re-render chosen multi-select box
                        jQuery(".chosen-select").trigger("chosen:updated");
                    }
                }
            }
        }
        // When the metabox button is clicked an AJAX request is sent back to the server which
        // triggers the ajax handler to get the most recently saved post information.
        // This info is then sent in a post request to Ingest.
        function postPackage() {

            if (!canPost) {
                return;
            }

            canPost = false;
            document.querySelector("#confirmPost").textContent = "Please wait"
            document.querySelector("#confirmPost").classList.add("vmn_ping__button--reductive_action");
            setTimeout(function () {
                canPost = true;
                document.querySelector("#confirmPost").textContent = "OK"
                document.querySelector("#confirmPost").classList.remove("vmn_ping__button--reductive_action");
            }, 10000)

            loc_element = document.getElementById("<?php echo PINGNEWS_LOCATION; ?>");
            sf_element = document.getElementById("<?php echo PINGNEWS_STORY_FOCUS; ?>");
            data = {
                'post_id': <?php echo $post_id; ?>,
                'location': loc_element.options[loc_element.selectedIndex].value,
                'story_focus': sf_element.options[sf_element.selectedIndex].text,
                'action' : 'pingnews_post_package',
                'nonce': '<?php echo wp_create_nonce('pingnews_post_package'); ?>',
            };

            if (<?php echo PINGNEWS_TRENDS_FEATURE_FLAG; ?>) {
                let existing_tags_element = document.getElementById("<?php echo PINGNEWS_EXISTING_TAGS; ?>");
                if (existing_tags_element) {
                    let selected_tags = [];
                    // Combine tags from chosen multi-select and tag text area to sent to Ingest
                    for (let i = 0; i < existing_tags_element.length; i++) {
                        if (existing_tags_element.options[i].selected) {
                            selected_tags.push(existing_tags_element.options[i].value);
                        }
                    }
                    data['tags'] = selected_tags.join(", ") + ", " + document.getElementById("<?php echo PINGNEWS_PACKAGE_TAGS; ?>").value;
                }
            }

            jQuery.ajax({
                type: 'POST',
                url: '<?php echo site_url(); ?>/wp-admin/admin-ajax.php',
                data: data,
                success: function(response) {
                    response = JSON.parse(response);
                    // Replace the innerHTML of the text in the metabox
                    if ( response["package_data"] ) {
                        package_data = response["package_data"];
                        document.getElementById("<?php echo PINGNEWS_PACKAGE_DATA_OUTER; ?>").style.display = "inline";

                        if ( package_data["package_token"] ) {
                            document.getElementById("<?php echo PINGNEWS_PACKAGE_TOKEN; ?>").innerHTML = package_data["package_token"];
                        }

                        if ( package_data["date_created"] ) {
                            document.getElementById("<?php echo PINGNEWS_PACKAGE_DATE_CREATED; ?>").innerHTML = package_data["date_created"];
                        }

                        updateDynamicInfo(package_data, true);
                    }
                    alert(response["message"]);
                    tb_remove();
                }
            });
        }

        function pollIngest(update_tags) {
            jQuery.ajax({
                type: 'POST',
                url: '<?php echo site_url(); ?>/wp-admin/admin-ajax.php',
                data: {
                    'post_id': <?php echo $post_id; ?>,
                    'action' : 'pingnews_get_package',
                    'nonce': '<?php echo wp_create_nonce('pingnews_get_package'); ?>',
                },
                success: function(response) {
                    response = JSON.parse(response);
                    // Replace the innerHTML of the text in the metabox
                    if ( response["package_data"] ) {
                        updateDynamicInfo(JSON.parse(response["package_data"]), update_tags);
                    }
                }
            });

            setTimeout(pollIngest.bind(null, false), <?php echo PINGNEWS_POLL_INTERVAL; ?>);
        }
    
        pollIngest(true);
    </script>
    <?php
}

/**
 * Function to echo option tags of the available taxonomy items
 *
 * @return void
 */
function pingnews_get_taxonomy_options($taxonomy_list, $selected_taxonomy) {
    echo '<option value="" selected>---</option>';
    foreach ($taxonomy_list as $taxonomy_item) {
        $selected = "";
        // Set the initially selected taxonomy by adding `selected` to option tag
        if ($taxonomy_item[0] == $selected_taxonomy) {
            $selected = "selected";
        }
        echo "<option value=\"".esc_attr($taxonomy_item[0])."\" $selected>".esc_html($taxonomy_item[1])."</option>";
    }
}

/**
 * Function to echo option tags of the available taxonomy items
 *
 * @return void
 */
function pingnews_get_tag_options($tag_list, $selected_tags_string) {
    $selected_tags_list = explode(", ", $selected_tags_string);

    foreach ($tag_list as $key => $tag_item) {
        $selected = "";
        // Set the initially selected tag by adding `selected` to option tag
        if (in_array($tag_item, $selected_tags_list)) {
            $selected = "selected=\"selected\"";
        }
        echo "<option value=\"".esc_attr($tag_item)."\" $selected>".esc_html($tag_item)."</option>";
    }
}

/**
 * The html to show when user has not entered authorization token in ping settings
 *
 * @return void
 */
function pingnews_metabox_no_auth_token_html() {
    ?>
        <p>Please enter your authorization token <a href="<?php menu_page_url(PINGNEWS_SETTINGS); ?>">here</a></p>
    <?php
}

/**
 * The php to render the pingnews metabox
 *
 * @return void
 */
function pingnews_metabox_php() {
    $auth_token = get_option( PINGNEWS_AUTH_TOKEN );

    // Refer user to settings page if authorization token not set
    if ( $auth_token ) {
        pingnews_metabox_html();
    } else {
        pingnews_metabox_no_auth_token_html();
    }
}

/**
 * Function which adds a metabox to all screen types defined in  $screens
 *
 * @return void
 */
function pingnews_add_metabox() {
    if ( pingnews_has_push_caps() ) {
        $screens = ['post'];

        foreach ($screens as $screen) {
            add_meta_box(
                'pingnews_box_id', // Unique ID
                'Ping!', // Box title
                'pingnews_metabox_php', // Content callback, must be of type callable
                $screen, // Post type
                'side'
            );
        }
    }
}

/**
 * Function to return an array of the categories associated with the post
 *
 * @return void
 */
function pingnews_get_categories_array($id) {
    $categories = get_the_category($id);
    $categories_array = [];

    foreach ($categories as $category) {
        array_push($categories_array, $category->name);
    }

    return $categories_array;
}

/**
 * Function to send a post request using WordPress HTTP API
 *
 * @return Array(Object, Integer)
 */
function pingnews_post_request($url, $body) {
    $args = array(
        "body" => $body,
        "timeout" => "30",
        "redirection" => "30",
        "httpversion" => "1.0",
        "headers" => array(
            "Authorization" => "Token " . get_option( PINGNEWS_AUTH_TOKEN ),
            "Content-Type" => "application/json",
            "Content-Length" => strlen($body)
        ),
    );

    // Send post request
    $response = wp_remote_post($url, $args);
    // Get http status from response
    $http_status = wp_remote_retrieve_response_code($response);
    // Get body content of response
    $body = wp_remote_retrieve_body($response);

    return [$body, $http_status];
}

/**
 * Function to send a get request using WordPress HTTP API
 *
 * @return Array(Object, Integer)
 */
function pingnews_get_request($url, $package_token) {
    $http_headers = array(
        "Authorization" => "Token " . get_option( PINGNEWS_AUTH_TOKEN ),
    );

    if ( $package_token ) {
        $http_headers["X-Package-Token"] = $package_token;
    }

    $args = array(
        "timeout" => "30",
        "redirection" => "30",
        "httpversion" => "1.0",
        "headers" => $http_headers,
    );

    // Send get request
    $response = wp_remote_get($url, $args);
    // Get http status from response
    $http_status = wp_remote_retrieve_response_code($response);
    // Get body content of response
    $body = wp_remote_retrieve_body($response);

    return [$body, $http_status];
}

/**
 * Function to return which a http_status code is considered valid
 *
 * @return Boolean
 */
function pingnews_is_http_status_valid($http_status) {
    // Check if http status code is successful (2xx)
    return strval($http_status)[0] == '2';
}

/**
 * Function to return the most recently saved post information and send this to Ingest in a
 * post request
 *
 * @return void
 */
function pingnews_post_package_ajax_handler() {
   $unsuccessful_message = "Unfortunately your push to ping was unsuccessful, please try again!";

   if ( pingnews_has_push_caps() &&
        isset($_POST["action"]) &&
        isset($_POST["nonce"]) &&
        $_POST["action"] === "pingnews_post_package" &&
        wp_verify_nonce($_POST["nonce"], "pingnews_post_package")
   ) {
     // verify we have a post id
     $post_id = (isset($_POST["post_id"])) ? (intval($_POST["post_id"])) : (null);
     // verify there is a post with such a number
     $post = get_post((int)$post_id);

     if (empty($post)) {
         echo json_encode([
             "message" => $unsuccessful_message,
             "package_data" => False
         ]);
         wp_die();
     }

     global $wp_version;
     // Find all urls for oEmbeds and replace with generated iframe
     $content = $post->post_content;
     // Remove newlines otherwise regex does not match
     $striped_content = str_replace(array("\n", "\r"), '', $content);
     // Find all occurrences of strings between div's with class "wp-block-embed__wrapper"
     preg_match_all('/<div class="wp-block-embed__wrapper">(.*?)<\/div>/', $striped_content, $matches);

     foreach ($matches[1] as $url) {
         // Generate oEmbed url
         $iframe_embed = wp_oembed_get($url);
         $content = str_replace("\n".$url."\n", $iframe_embed, $content);
     }

    // match any WP 4 image caption
    //      example:    [caption id="attachment_7" align="alignnone" width="218"]<img ... /> Caption text[/caption]
    preg_match_all('/\[caption\ id="[A-Za-z0-9_]*"\ align="[A-Za-z]*" width="[0-9]*"][ \n]*<img .*\/>(.*)\[\/caption]/', $content, $caption_matches);

    // replace each match with a figcaption element
    foreach ($caption_matches[0] as $match) {
        // remove opening `[caption]`
        $replace = preg_replace('/\[caption\ id="[A-Za-z0-9_]*"\ align="[A-Za-z]*" width="[0-9]*"]/', "", $match);
        // replace caption str and closing `[/caption]` with a <figcaption> element
        $replace = preg_replace('/\/>(.*)\[\/caption]/', '/> <figcaption>${1}</figcaption>', $replace);
        // update content
        $content = str_replace($match, $replace, $content);
    }

     // Construct data to send in post request to ingest
     $post_data = [
         "package_data" => [
             "title" => $post->post_title,
             "body" => $content,
             "date_provider_published" => $post->post_date . '+' . zeroise(get_option("gmt_offset"), 2),
             "meta" => [
                 "permalink" => $post->guid,
                 "featured_image" => get_the_post_thumbnail_url($post),
                 "featured_image_caption" => get_the_post_thumbnail_caption($post),
                 "author" => $post->post_author,
                 "categories" => pingnews_get_categories_array($post_id),
                 "cms_version" => $wp_version,
                 "location" => sanitize_text_field($_POST["location"]),
                 "story_focus" => sanitize_text_field($_POST["story_focus"]),
                 "excerpt" => get_the_excerpt($post_id)
             ]
         ],
         "type" => "wordpress-".$wp_version,
         "plugin_version" => PINGNEWS_PLUGIN_VERSION,
         "tags" => sanitize_text_field($_POST["tags"]),
     ];

     $post_json = json_encode($post_data);
     list($package_data, $http_status) = pingnews_post_request(PINGNEWS_VMN_INGEST_URL . "/api/v1/package/", $post_json);

     if (pingnews_is_http_status_valid($http_status) && $package_data) {
         // When posting new story feedback can be cleared if received for a previous version of the post
        //  $package_data["feedback"] = "";
         add_post_meta($post_id, PINGNEWS_PACKAGE_DATA, $package_data);
         $message = "You have successfully pushed to Ping!";
         $package_json = json_decode($package_data);

         // Convert date times to UTC and r format
        //  $date_provider_published = new DateTime($package_json->package_data->date_provider_published, new DateTimeZone("UTC"));
        //  $package_json->package_data->date_provider_published = $date_provider_published->format("r");
        //  $date_created = new DateTime($package_json->date_created, new DateTimeZone("UTC"));
        //  $package_json->date_created = $date_created->format("r");
     } else {
         if ($http_status == 400) {
             // In this scenario package_data will be the error message returned from the Ingest API
             $message = $package_data;
         } else {
            $message = $unsuccessful_message;
         }
         $package_json = False;
     }

     echo json_encode([
         "message" => $message,
         "package_data" => $package_json
     ]);

     wp_die();
   }
   echo json_encode([
       "message" => $unsuccessful_message,
       "package_data" => False
   ]);
   wp_die();
}

/**
 * Function to get most recent version of the last sent package to Ingest, to be able to e.g.
 * get the latest acceptance state
 *
 * @return void
 */
function pingnews_get_package_ajax_handler() {
   if ( pingnews_has_push_caps() &&
        isset($_POST["action"]) &&
        isset($_POST["nonce"]) &&
        $_POST["action"] === "pingnews_get_package" &&
        wp_verify_nonce($_POST["nonce"], "pingnews_get_package")
   ) {
     $post_id = (isset($_POST["post_id"])) ? (intval($_POST["post_id"])) : (null);
     if ( $post_id ) {
         $last_package_data = end(get_post_meta($post_id, PINGNEWS_PACKAGE_DATA));

         if ( $last_package_data ) {
             $last_package_json = json_decode($last_package_data);
             $last_package_data = end(get_post_meta($post_id, PINGNEWS_PACKAGE_DATA));
             list($package_data, $http_status) = pingnews_get_request(PINGNEWS_VMN_INGEST_URL . "/api/v1/package/", $last_package_json->package_token);
             $package_json = json_decode($package_data);

             // Check whether data has changed since the last time the metadata was saved to WordPress
             // Otherwise can duplicate data needlessly
             if (pingnews_is_http_status_valid($http_status) && $package_data && ($package_json->acceptance_state != $last_package_json->acceptance_state || $package_json->feedback != $last_package_json->feedback)) {
                 add_post_meta($post_id, PINGNEWS_PACKAGE_DATA, $package_data);
             }
         }
     }
     echo json_encode([
       "package_data" => $package_data
     ]);
   }
   wp_die();
}

/**
 * Function to get the lists of possible taxonomies
 *
 * @return object
 */
function pingnews_get_taxonomies() {
   list($taxonomies, $http_status) = pingnews_get_request(PINGNEWS_VMN_INGEST_URL . "/api/v1/taxonomies/", False);

   // Raise exception if failed to get taxonomies
   if (!pingnews_is_http_status_valid($http_status) || !$taxonomies) {
       throw new Exception("Unable to reach Ping! please refresh the page to try again or check your authorization token is correct.");
   }

   return json_decode($taxonomies);
}

/**
 * Function to get the lists of current tags
 *
 * @return object
 */
function pingnews_get_existing_tags() {
   list($tags, $http_status) = pingnews_get_request(PINGNEWS_VMN_CENTRAL_URL . "/api/v1/tags/", False);

   if (!$tags) {
       $tags = False;
   }

   return json_decode($tags);
}
?>
