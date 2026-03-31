<?php

use MailPoetVendor\Twig\Environment;
use MailPoetVendor\Twig\Error\LoaderError;
use MailPoetVendor\Twig\Error\RuntimeError;
use MailPoetVendor\Twig\Extension\SandboxExtension;
use MailPoetVendor\Twig\Markup;
use MailPoetVendor\Twig\Sandbox\SecurityError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedTagError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedFilterError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedFunctionError;
use MailPoetVendor\Twig\Source;
use MailPoetVendor\Twig\Template;

/* layout.html */
class __TwigTemplate_b66b38567bf2ff14880471ee0407a19a333a1a8ce4c1c6d79b3c6302410a56b6 extends \MailPoetVendor\Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'templates' => [$this, 'block_templates'],
            'container' => [$this, 'block_container'],
            'title' => [$this, 'block_title'],
            'content' => [$this, 'block_content'],
            'after_css' => [$this, 'block_after_css'],
            'translations' => [$this, 'block_translations'],
            'after_translations' => [$this, 'block_after_translations'],
            'after_javascript' => [$this, 'block_after_javascript'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        if (($context["sub_menu"] ?? null)) {
            // line 2
            echo "<script type=\"text/javascript\">
jQuery('#adminmenu #toplevel_page_mailpoet-newsletters')
  .addClass('wp-has-current-submenu')
  .find('a[href\$=\"";
            // line 5
            echo \MailPoetVendor\twig_escape_filter($this->env, ($context["sub_menu"] ?? null), "html", null, true);
            echo "\"]')
  .addClass('current')
  .parent()
  .addClass('current');
</script>
";
        }
        // line 11
        echo "
<!-- pre connect to 3d party to speed up page loading -->
<link rel=\"preconnect\" href=\"https://beacon-v2.helpscout.net/\">
<link rel=\"dns-prefetch\" href=\"https://beacon-v2.helpscout.net/\">
<link rel=\"preconnect\" href=\"http://cdn.mxpnl.com\">
<link rel=\"dns-prefetch\" href=\"http://cdn.mxpnl.com\">

<!-- system notices -->
<div id=\"mailpoet_notice_system\" class=\"mailpoet_notice\" style=\"display:none;\"></div>

<!-- handlebars templates -->
";
        // line 22
        $this->displayBlock('templates', $context, $blocks);
        // line 23
        echo "
<!-- main container -->
";
        // line 25
        $this->displayBlock('container', $context, $blocks);
        // line 42
        echo "
<!-- stylesheets -->
";
        // line 44
        echo $this->extensions['MailPoet\Twig\Assets']->generateStylesheet("mailpoet-font.css", "mailpoet-plugin.css");
        // line 47
        echo "

";
        // line 49
        echo do_action("mailpoet_styles_admin_after");
        echo "

";
        // line 51
        $this->displayBlock('after_css', $context, $blocks);
        // line 52
        echo "
<script type=\"text/javascript\">
  var mailpoet_datetime_format = \"";
        // line 54
        echo \MailPoetVendor\twig_escape_filter($this->env, \MailPoetVendor\twig_escape_filter($this->env, $this->extensions['MailPoet\Twig\Functions']->getWPDateTimeFormat(), "js"), "html", null, true);
        echo "\";
  var mailpoet_date_format = \"";
        // line 55
        echo \MailPoetVendor\twig_escape_filter($this->env, \MailPoetVendor\twig_escape_filter($this->env, $this->extensions['MailPoet\Twig\Functions']->getWPDateFormat(), "js"), "html", null, true);
        echo "\";
  var mailpoet_time_format = \"";
        // line 56
        echo \MailPoetVendor\twig_escape_filter($this->env, \MailPoetVendor\twig_escape_filter($this->env, $this->extensions['MailPoet\Twig\Functions']->getWPTimeFormat(), "js"), "html", null, true);
        echo "\";
  var mailpoet_version = \"";
        // line 57
        echo $this->extensions['MailPoet\Twig\Functions']->getMailPoetVersion();
        echo "\";
  var mailpoet_locale = \"";
        // line 58
        echo $this->extensions['MailPoet\Twig\Functions']->getTwoLettersLocale();
        echo "\";
  var mailpoet_premium_version = ";
        // line 59
        echo json_encode($this->extensions['MailPoet\Twig\Functions']->getMailPoetPremiumVersion());
        echo ";
  var mailpoet_3rd_party_libs_enabled = ";
        // line 60
        echo \MailPoetVendor\twig_escape_filter($this->env, json_encode($this->extensions['MailPoet\Twig\Functions']->libs3rdPartyEnabled()), "html", null, true);
        echo ";
  var mailpoet_analytics_enabled = ";
        // line 61
        echo \MailPoetVendor\twig_escape_filter($this->env, json_encode(call_user_func_array($this->env->getFunction('is_analytics_enabled')->getCallable(), [])), "html", null, true);
        echo ";
  var mailpoet_analytics_data = ";
        // line 62
        echo json_encode(call_user_func_array($this->env->getFunction('get_analytics_data')->getCallable(), []));
        echo ";
  var mailpoet_analytics_public_id = ";
        // line 63
        echo json_encode(call_user_func_array($this->env->getFunction('get_analytics_public_id')->getCallable(), []));
        echo ";
  var mailpoet_analytics_new_public_id = ";
        // line 64
        echo \MailPoetVendor\twig_escape_filter($this->env, json_encode(call_user_func_array($this->env->getFunction('is_analytics_public_id_new')->getCallable(), [])), "html", null, true);
        echo ";
  var mailpoet_free_domains = ";
        // line 65
        echo json_encode($this->extensions['MailPoet\Twig\Functions']->getFreeDomains());
        echo ";
  var mailpoet_woocommerce_active = ";
        // line 66
        echo json_encode($this->extensions['MailPoet\Twig\Functions']->isWoocommerceActive());
        echo ";
  // RFC 5322 standard; http://emailregex.com/ combined with https://google.github.io/closure-library/api/goog.format.EmailAddress.html#isValid
  var mailpoet_email_regex = /(?=^[+a-zA-Z0-9_.!#\$%&'*\\/=?^`{|}~-]+@([a-zA-Z0-9-]+\\.)+[a-zA-Z0-9]{2,63}\$)(?=^(([^<>()\\[\\]\\\\.,;:\\s@\"]+(\\.[^<>()\\[\\]\\\\.,;:\\s@\"]+)*)|(\".+\"))@((\\[[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}])|(([a-zA-Z\\-0-9]+\\.)+[a-zA-Z]{2,})))/;
  var mailpoet_feature_flags = ";
        // line 69
        echo json_encode(($context["feature_flags"] ?? null));
        echo ";
  var mailpoet_referral_id = ";
        // line 70
        echo json_encode(($context["referral_id"] ?? null));
        echo ";
  var mailpoet_feature_announcement_has_news = ";
        // line 71
        echo json_encode(($context["feature_announcement_has_news"] ?? null));
        echo ";
  var mailpoet_wp_segment_state = ";
        // line 72
        echo json_encode(($context["wp_segment_state"] ?? null));
        echo ";
</script>

<!-- javascripts -->
";
        // line 76
        echo $this->extensions['MailPoet\Twig\Assets']->generateJavascript("vendor.js", "mailpoet.js");
        // line 79
        echo "

";
        // line 81
        echo $this->extensions['MailPoet\Twig\I18n']->localize(["ajaxFailedErrorMessage" => $this->extensions['MailPoet\Twig\I18n']->translate("An error has happened while performing a request, the server has responded with response code %d"), "ajaxTimeoutErrorMessage" => $this->extensions['MailPoet\Twig\I18n']->translate("An error has happened while performing a request, the server request has timed out after %d seconds"), "senderEmailAddressWarning1" => $this->extensions['MailPoet\Twig\I18n']->translateWithContext("You might not reach the inbox of your subscribers if you use this email address.", "In the last step, before sending a newsletter. URL: ?page=mailpoet-newsletters#/send/2"), "senderEmailAddressWarning2" => $this->extensions['MailPoet\Twig\I18n']->translateWithContext("Use an address like %1\$s for the Sender and put %2\$s in the <em>Reply-to</em> field below.", "In the last step, before sending a newsletter. URL: ?page=mailpoet-newsletters#/send/2"), "senderEmailAddressWarning3" => $this->extensions['MailPoet\Twig\I18n']->translateWithContext("Read more."), "mailerSendingResumedNotice" => $this->extensions['MailPoet\Twig\I18n']->translate("Sending has been resumed."), "dismissNotice" => $this->extensions['MailPoet\Twig\I18n']->translate("Dismiss this notice."), "subscribersLimitNoticeTitle" => $this->extensions['MailPoet\Twig\I18n']->translate("Congratulations, you now have more than [subscribersLimit] subscribers!"), "freeVersionLimit" => $this->extensions['MailPoet\Twig\I18n']->translate("Our free version is limited to [subscribersLimit] subscribers."), "yourPlanLimit" => $this->extensions['MailPoet\Twig\I18n']->translate("Your plan is limited to [subscribersLimit] subscribers."), "youNeedToUpgrade" => $this->extensions['MailPoet\Twig\I18n']->translate("You need to upgrade now to be able to continue using MailPoet."), "youCanDisableWPUsersList" => $this->extensions['MailPoet\Twig\I18n']->translate("If you do not send emails to your WordPress Users list, you can [link]disable it[/link] to lower your number of subscribers."), "upgradeNow" => $this->extensions['MailPoet\Twig\I18n']->translate("Upgrade Now"), "refreshMySubscribers" => $this->extensions['MailPoet\Twig\I18n']->translate("I’ve upgraded my subscription, refresh subscriber limit"), "setFromAddressModalTitle" => $this->extensions['MailPoet\Twig\I18n']->translate("It’s time to set your default FROM address!", "mailpoet"), "setFromAddressModalDescription" => $this->extensions['MailPoet\Twig\I18n']->translate("Set one of [link]your authorized email addresses[/link] as the default FROM email for your MailPoet emails.", "mailpoet"), "setFromAddressModalSave" => $this->extensions['MailPoet\Twig\I18n']->translate("Save", "mailpoet"), "setFromAddressEmailSuccess" => $this->extensions['MailPoet\Twig\I18n']->translate("Excellent. Your authorized email was saved. You can change it in the [link]Basics tab of the MailPoet settings[/link].", "mailpoet"), "setFromAddressEmailNotAuthorized" => $this->extensions['MailPoet\Twig\I18n']->translate("Can’t use this email yet! [link]Please authorize it first[/link].", "mailpoet"), "setFromAddressEmailUnknownError" => $this->extensions['MailPoet\Twig\I18n']->translate("An error occured when saving FROM email address.", "mailpoet"), "reviewRequestHeading" => $this->extensions['MailPoet\Twig\I18n']->translateWithContext("Thank you! Time to tell the world?", "After a user gives us positive feedback via the NPS poll, we ask them to review our plugin on WordPress.org."), "reviewRequestDidYouKnow" => $this->extensions['MailPoet\Twig\I18n']->translate("[username], did you know that hundreds of WordPress users read the reviews on the plugin repository? They’re also a source of inspiration for our team."), "reviewRequestUsingForDays" => $this->extensions['MailPoet\Twig\I18n']->pluralize("You’ve been using MailPoet for [days] day now, and we would love to read your own review.", "You’ve been using MailPoet for [days] days now, and we would love to read your own review.",         // line 107
($context["installed_days_ago"] ?? null)), "reviewRequestUsingForMonths" => $this->extensions['MailPoet\Twig\I18n']->pluralize("You’ve been using MailPoet for [months] month now, and we would love to read your own review.", "You’ve been using MailPoet for [months] months now, and we would love to read your own review.", \MailPoetVendor\twig_round((        // line 108
($context["installed_days_ago"] ?? null) / 30))), "reviewRequestRateUsNow" => $this->extensions['MailPoet\Twig\I18n']->translateWithContext("Rate us now", "Review our plugin on WordPress.org."), "reviewRequestNotNow" => $this->extensions['MailPoet\Twig\I18n']->translate("Not now"), "sent" => $this->extensions['MailPoet\Twig\I18n']->translate("Sent"), "notSentYet" => $this->extensions['MailPoet\Twig\I18n']->translate("Not sent yet!"), "close" => $this->extensions['MailPoet\Twig\I18n']->translate("Close"), "today" => $this->extensions['MailPoet\Twig\I18n']->translate("Today"), "january" => $this->extensions['MailPoet\Twig\I18n']->translate("January"), "february" => $this->extensions['MailPoet\Twig\I18n']->translate("February"), "march" => $this->extensions['MailPoet\Twig\I18n']->translate("March"), "april" => $this->extensions['MailPoet\Twig\I18n']->translate("April"), "may" => $this->extensions['MailPoet\Twig\I18n']->translate("May"), "june" => $this->extensions['MailPoet\Twig\I18n']->translate("June"), "july" => $this->extensions['MailPoet\Twig\I18n']->translate("July"), "august" => $this->extensions['MailPoet\Twig\I18n']->translate("August"), "september" => $this->extensions['MailPoet\Twig\I18n']->translate("September"), "october" => $this->extensions['MailPoet\Twig\I18n']->translate("October"), "november" => $this->extensions['MailPoet\Twig\I18n']->translate("November"), "december" => $this->extensions['MailPoet\Twig\I18n']->translate("December"), "januaryShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Jan"), "februaryShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Feb"), "marchShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Mar"), "aprilShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Apr"), "mayShort" => $this->extensions['MailPoet\Twig\I18n']->translate("May"), "juneShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Jun"), "julyShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Jul"), "augustShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Aug"), "septemberShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Sep"), "octoberShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Oct"), "novemberShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Nov"), "decemberShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Dec"), "sundayShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Sun"), "mondayShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Mon"), "tuesdayShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Tue"), "wednesdayShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Wed"), "thursdayShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Thu"), "fridayShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Fri"), "saturdayShort" => $this->extensions['MailPoet\Twig\I18n']->translate("Sat"), "sundayMin" => $this->extensions['MailPoet\Twig\I18n']->translateWithContext("S", "Sunday - one letter abbreviation"), "mondayMin" => $this->extensions['MailPoet\Twig\I18n']->translateWithContext("M", "Monday - one letter abbreviation"), "tuesdayMin" => $this->extensions['MailPoet\Twig\I18n']->translateWithContext("T", "Tuesday - one letter abbreviation"), "wednesdayMin" => $this->extensions['MailPoet\Twig\I18n']->translateWithContext("W", "Wednesday - one letter abbreviation"), "thursdayMin" => $this->extensions['MailPoet\Twig\I18n']->translateWithContext("T", "Thursday - one letter abbreviation"), "fridayMin" => $this->extensions['MailPoet\Twig\I18n']->translateWithContext("F", "Friday - one letter abbreviation"), "saturdayMin" => $this->extensions['MailPoet\Twig\I18n']->translateWithContext("S", "Saturday - one letter abbreviation")]);
        // line 155
        echo "
";
        // line 156
        $this->displayBlock('translations', $context, $blocks);
        // line 157
        echo "
";
        // line 158
        $this->displayBlock('after_translations', $context, $blocks);
        // line 159
        echo "
";
        // line 160
        echo $this->extensions['MailPoet\Twig\Assets']->generateJavascript("admin_vendor_chunk.js", "admin_vendor.js");
        // line 163
        echo "

";
        // line 165
        echo do_action("mailpoet_scripts_admin_before");
        echo "

";
        // line 167
        echo $this->extensions['MailPoet\Twig\Assets']->generateJavascript("admin.js");
        // line 169
        echo "

";
        // line 171
        if ($this->extensions['MailPoet\Twig\Functions']->libs3rdPartyEnabled()) {
            // line 172
            echo "  ";
            echo $this->extensions['MailPoet\Twig\Assets']->generateJavascript("lib/analytics.js");
            echo "

  ";
            // line 174
            $context["helpscout_form_id"] = "1c666cab-c0f6-4614-bc06-e5d0ad78db2b";
            // line 175
            echo "  ";
            if (((\MailPoetVendor\twig_get_attribute($this->env, $this->source, \MailPoetVendor\twig_get_attribute($this->env, $this->source, ($context["mailpoet_api_key_state"] ?? null), "data", [], "any", false, false, false, 175), "support_tier", [], "any", false, false, false, 175) == "premium") || (\MailPoetVendor\twig_get_attribute($this->env, $this->source, \MailPoetVendor\twig_get_attribute($this->env, $this->source, ($context["premium_key_state"] ?? null), "data", [], "any", false, false, false, 175), "support_tier", [], "any", false, false, false, 175) == "premium"))) {
                // line 176
                echo "    ";
                $context["helpscout_form_id"] = "e93d0423-1fa6-4bbc-9df9-c174f823c35f";
                // line 177
                echo "  ";
            }
            // line 178
            echo "
  <script type=\"text/javascript\">!function(e,t,n){function a(){var e=t.getElementsByTagName(\"script\")[0],n=t.createElement(\"script\");n.type=\"text/javascript\",n.async=!0,n.src=\"https://beacon-v2.helpscout.net\",e.parentNode.insertBefore(n,e)}if(e.Beacon=n=function(t,n,a){e.Beacon.readyQueue.push({method:t,options:n,data:a})},n.readyQueue=[],\"complete\"===t.readyState)return a();e.attachEvent?e.attachEvent(\"onload\",a):e.addEventListener(\"load\",a,!1)}(window,document,window.Beacon||function(){});</script>

  <script type=\"text/javascript\"></script>

  <script type=\"text/javascript\">
    if(window['Beacon'] !== undefined && window.hide_mailpoet_beacon !== true) {
      window.Beacon('init', '";
            // line 185
            echo \MailPoetVendor\twig_escape_filter($this->env, ($context["helpscout_form_id"] ?? null), "html", null, true);
            echo "');

      // HelpScout Beacon: Configuration
      window.Beacon(\"config\", {
        icon: 'message',
        zIndex: 50000,
        instructions: \"";
            // line 191
            echo $this->extensions['MailPoet\Twig\I18n']->translate("Want to give feedback to the MailPoet team? Contact us here. Please provide as much information as possible!");
            echo "\",
        showContactFields: true
      });

      // HelpScout Beacon: Custom information
      window.Beacon(\"identify\",
        ";
            // line 197
            echo json_encode($this->extensions['MailPoet\Twig\Helpscout']->getHelpscoutData());
            echo "
      );

      if (window.mailpoet_beacon_articles) {
        window.Beacon('suggest', window.mailpoet_beacon_articles)
      }
    }
  </script>
";
        }
        // line 206
        echo "<script>
  Parsley.addMessages('mailpoet', {
    defaultMessage: '";
        // line 208
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This value seems to be invalid.");
        echo "',
    type: {
      email: '";
        // line 210
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This value should be a valid email.");
        echo "',
      url: '";
        // line 211
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This value should be a valid url.");
        echo "',
      number: '";
        // line 212
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This value should be a valid number.");
        echo "',
      integer: '";
        // line 213
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This value should be a valid integer.");
        echo "',
      digits: '";
        // line 214
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This value should be digits.");
        echo "',
      alphanum: '";
        // line 215
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This value should be alphanumeric.");
        echo "'
    },
    notblank: '";
        // line 217
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This value should not be blank.");
        echo "',
    required: '";
        // line 218
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This value is required.");
        echo "',
    pattern: '";
        // line 219
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This value seems to be invalid.");
        echo "',
    min: '";
        // line 220
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This value should be greater than or equal to %s.");
        echo "',
    max: '";
        // line 221
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This value should be lower than or equal to %s.");
        echo "',
    range: '";
        // line 222
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This value should be between %s and %s.");
        echo "',
    minlength: '";
        // line 223
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This value is too short. It should have %s characters or more.");
        echo "',
    maxlength: '";
        // line 224
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This value is too long. It should have %s characters or fewer.");
        echo "',
    length: '";
        // line 225
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This value length is invalid. It should be between %s and %s characters long.");
        echo "',
    mincheck: '";
        // line 226
        echo $this->extensions['MailPoet\Twig\I18n']->translate("You must select at least %s choices.");
        echo "',
    maxcheck: '";
        // line 227
        echo $this->extensions['MailPoet\Twig\I18n']->translate("You must select %s choices or fewer.");
        echo "',
    check: '";
        // line 228
        echo $this->extensions['MailPoet\Twig\I18n']->translate("You must select between %s and %s choices.");
        echo "',
    equalto: '";
        // line 229
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This value should be the same.");
        echo "'
  });

  Parsley.setLocale('mailpoet');
</script>
";
        // line 234
        $this->displayBlock('after_javascript', $context, $blocks);
        // line 235
        echo "<div id=\"mailpoet-modal\"></div>
";
    }

    // line 22
    public function block_templates($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    // line 25
    public function block_container($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 26
        echo "<div class=\"wrap\">
  <!-- notices -->
  <div id=\"mailpoet_notice_error\" class=\"mailpoet_notice\" style=\"display:none;\"></div>
  <div id=\"mailpoet_notice_success\" class=\"mailpoet_notice\" style=\"display:none;\"></div>
  <!-- React notices -->
  <div id=\"mailpoet_notices\"></div>

  <!-- Set FROM address modal React root -->
  <div id=\"mailpoet_set_from_address_modal\"></div>

  <!-- title block -->
  ";
        // line 37
        $this->displayBlock('title', $context, $blocks);
        // line 38
        echo "  <!-- content block -->
  ";
        // line 39
        $this->displayBlock('content', $context, $blocks);
        // line 40
        echo "</div>
";
    }

    // line 37
    public function block_title($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    // line 39
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    // line 51
    public function block_after_css($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    // line 156
    public function block_translations($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    // line 158
    public function block_after_translations($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    // line 234
    public function block_after_javascript($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    public function getTemplateName()
    {
        return "layout.html";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  436 => 234,  430 => 158,  424 => 156,  418 => 51,  412 => 39,  406 => 37,  401 => 40,  399 => 39,  396 => 38,  394 => 37,  381 => 26,  377 => 25,  371 => 22,  366 => 235,  364 => 234,  356 => 229,  352 => 228,  348 => 227,  344 => 226,  340 => 225,  336 => 224,  332 => 223,  328 => 222,  324 => 221,  320 => 220,  316 => 219,  312 => 218,  308 => 217,  303 => 215,  299 => 214,  295 => 213,  291 => 212,  287 => 211,  283 => 210,  278 => 208,  274 => 206,  262 => 197,  253 => 191,  244 => 185,  235 => 178,  232 => 177,  229 => 176,  226 => 175,  224 => 174,  218 => 172,  216 => 171,  212 => 169,  210 => 167,  205 => 165,  201 => 163,  199 => 160,  196 => 159,  194 => 158,  191 => 157,  189 => 156,  186 => 155,  184 => 108,  183 => 107,  182 => 81,  178 => 79,  176 => 76,  169 => 72,  165 => 71,  161 => 70,  157 => 69,  151 => 66,  147 => 65,  143 => 64,  139 => 63,  135 => 62,  131 => 61,  127 => 60,  123 => 59,  119 => 58,  115 => 57,  111 => 56,  107 => 55,  103 => 54,  99 => 52,  97 => 51,  92 => 49,  88 => 47,  86 => 44,  82 => 42,  80 => 25,  76 => 23,  74 => 22,  61 => 11,  52 => 5,  47 => 2,  45 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "layout.html", "/home/appsfadmin/public_html/wp-content/plugins/mailpoet/views/layout.html");
    }
}
