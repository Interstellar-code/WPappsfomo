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

/* emails/congratulatoryMssEmail.txt */
class __TwigTemplate_ff8091f57f0900aa375ab36a3b9c342133795a3a7d297aed4e7b477caa56e785 extends \MailPoetVendor\Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo $this->extensions['MailPoet\Twig\I18n']->translate("Congrats!");
        echo "

";
        // line 3
        echo $this->extensions['MailPoet\Twig\I18n']->translate("MailPoet is now sending your emails");
        echo "

";
        // line 5
        echo $this->extensions['MailPoet\Twig\I18n']->translate("This email was sent automatically with the MailPoet Sending Service after you activated your key in your MailPoet settings.");
        echo "
";
    }

    public function getTemplateName()
    {
        return "emails/congratulatoryMssEmail.txt";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  47 => 5,  42 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "emails/congratulatoryMssEmail.txt", "/home/appsfadmin/public_html/wp-content/plugins/mailpoet/views/emails/congratulatoryMssEmail.txt");
    }
}
