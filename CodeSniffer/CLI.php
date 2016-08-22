<?php
/**
 * A class to process command line phpcs scripts.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2011 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */


  include_once 'CodeSniffer.php';

/**
 * A class to process command line phpcs scripts.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2011 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: 1.3.5
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class PHP_CodeSniffer_CLI
{

    /**
     * An array of all values specified on the command line.
     *
     * @var array
     */
    protected $values = array();

    /**
     * The minimum severity level errors must have to be displayed.
     *
     * @var bool
     */
    public $errorSeverity = 0;

    /**
     * The minimum severity level warnings must have to be displayed.
     *
     * @var bool
     */
    public $warningSeverity = 0;


    /**
     * Exits if the minimum requirements of PHP_CodSniffer are not met.
     *
     * @return array
     */
    public function checkRequirements()
    {
        // Check the PHP version.
        if (version_compare(PHP_VERSION, '5.1.2') === -1) {
            echo 'ERROR: PHP_CodeSniffer requires PHP version 5.1.2 or greater.'.PHP_EOL;
            exit(2);
        }

        if (extension_loaded('tokenizer') === false) {
            echo 'ERROR: PHP_CodeSniffer requires the tokenizer extension to be enabled.'.PHP_EOL;
            exit(2);
        }

    }//end checkRequirements()


    /**
     * Get a list of default values for all possible command line arguments.
     *
     * @return array
     */
    public function getDefaults()
    {
        // The default values for config settings.
        $defaults['files']           = array();
        $defaults['standard']        = null;
        $defaults['verbosity']       = 0;
        $defaults['interactive']     = false;
        $defaults['local']           = false;
        $defaults['showSources']     = false;
        $defaults['extensions']      = array();
        $defaults['sniffs']          = array();
        $defaults['ignored']         = array();
        $defaults['reportFile']      = null;
        $defaults['generator']       = '';
        $defaults['reports']         = array();
        $defaults['errorSeverity']   = null;
        $defaults['warningSeverity'] = null;

        $reportFormat = PHP_CodeSniffer::getConfigData('report_format');
        if ($reportFormat !== null) {
            $defaults['reports'][$reportFormat] = null;
        }

        $tabWidth = PHP_CodeSniffer::getConfigData('tab_width');
        if ($tabWidth === null) {
            $defaults['tabWidth'] = 0;
        } else {
            $defaults['tabWidth'] = (int) $tabWidth;
        }

        $encoding = PHP_CodeSniffer::getConfigData('encoding');
        if ($encoding === null) {
            $defaults['encoding'] = 'iso-8859-1';
        } else {
            $defaults['encoding'] = strtolower($encoding);
        }

        $severity = PHP_CodeSniffer::getConfigData('severity');
        if ($severity !== null) {
            $defaults['errorSeverity']   = (int) $severity;
            $defaults['warningSeverity'] = (int) $severity;
        }

        $severity = PHP_CodeSniffer::getConfigData('error_severity');
        if ($severity !== null) {
            $defaults['errorSeverity'] = (int) $severity;
        }

        $severity = PHP_CodeSniffer::getConfigData('warning_severity');
        if ($severity !== null) {
            $defaults['warningSeverity'] = (int) $severity;
        }

        $showWarnings = PHP_CodeSniffer::getConfigData('show_warnings');
        if ($showWarnings !== null) {
            $showWarnings = (bool) $showWarnings;
            if ($showWarnings === false) {
                $defaults['warningSeverity'] = 0;
            }
        }

        $reportWidth = PHP_CodeSniffer::getConfigData('report_width');
        if ($reportWidth === null) {
            $defaults['reportWidth'] = 80;
        } else {
            $defaults['reportWidth'] = (int) $reportWidth;
        }

        $showProgress = PHP_CodeSniffer::getConfigData('show_progress');
        if ($showProgress === null) {
            $defaults['showProgress'] = false;
        } else {
            $defaults['showProgress'] = (bool) $showProgress;
        }

        return $defaults;

    }//end getDefaults()


    /**
     * Process the command line arguments and returns the values.
     *
     * @return array
     */
    public function getCommandLineValues()
    {
        if (empty($this->values) === false) {
            return $this->values;
        }

        $values = $this->getDefaults();

        for ($i = 1; $i < $_SERVER['argc']; $i++) {
            $arg = $_SERVER['argv'][$i];
            if ($arg === '') {
                continue;
            }

            if ($arg{0} === '-') {
                if ($arg === '-' || $arg === '--') {
                    // Empty argument, ignore it.
                    continue;
                }

                if ($arg{1} === '-') {
                    $values
                        = $this->processLongArgument(substr($arg, 2), $i, $values);
                } else {
                    $switches = str_split($arg);
                    foreach ($switches as $switch) {
                        if ($switch === '-') {
                            continue;
                        }

                        $values = $this->processShortArgument($switch, $i, $values);
                    }
                }
            } else {
                $values = $this->processUnknownArgument($arg, $i, $values);
            }//end if
        }//end for

        $this->values = $values;
        return $values;

    }//end getCommandLineValues()




    public function processLongArgument($arg, $pos, $values)
    {
        $values['standard'] = substr($arg, 9);

        if ($values['standard'] == null) {
            $values['standard'] = "DM";
        }

        return $values;

    }


    public function processUnknownArgument($arg, $pos, $values)
    {
        $file = realpath($arg);
        $values['files'][] = $file;

        return $values;

    }


    public function process($values=array())
    {
        if (empty($values) === true) {
            $values = $this->getCommandLineValues();
        }

        $fileContents = '';
        if (empty($values['files']) === true) {
            // Check if they passing in the file contents.
            $handle       = fopen('php://stdin', 'r');
            $fileContents = stream_get_contents($handle);
            fclose($handle);

            if ($fileContents === '') {
                // No files and no content passed in.
                echo 'ERROR: You must supply at least one file or directory to process.'.PHP_EOL.PHP_EOL;
                $this->printUsage();
                exit(2);
            }
        }

        $values['standard'] = $this->validateStandard($values['standard']);

        $phpcs = new PHP_CodeSniffer(
            $values['verbosity'],
            $values['tabWidth'],
            $values['encoding'],
            $values['interactive']
        );

        // Set file extensions if they were specified. Otherwise,
        // let PHP_CodeSniffer decide on the defaults.
        if (empty($values['extensions']) === false) {
            $phpcs->setAllowedFileExtensions($values['extensions']);
        }

        // Set ignore patterns if they were specified.
        if (empty($values['ignored']) === false) {
            $phpcs->setIgnorePatterns($values['ignored']);
        }

        // Set some convenience member vars.
        if ($values['errorSeverity'] === null) {
            $this->errorSeverity = PHPCS_DEFAULT_ERROR_SEV;
        } else {
            $this->errorSeverity = $values['errorSeverity'];
        }

        if ($values['warningSeverity'] === null) {
            $this->warningSeverity = PHPCS_DEFAULT_WARN_SEV;
        } else {
            $this->warningSeverity = $values['warningSeverity'];
        }

        $phpcs->setCli($this);

        $phpcs->process(
            $values['files'],
            $values['standard'],
            $values['sniffs'],
            $values['local']
        );

        if ($fileContents !== '') {
            $phpcs->processFile('STDIN', $fileContents);
        }

        return $this->printErrorReport(
            $phpcs,
            $values['reports'],
            $values['showSources'],
            $values['reportFile'],
            $values['reportWidth']
        );

    }//end process()


    /**
     * Prints the error report for the run.
     *
     * Note that this function may actually print multiple reports
     * as the user may have specified a number of output formats.
     *
     * @param PHP_CodeSniffer $phpcs       The PHP_CodeSniffer object containing
     *                                     the errors.
     * @param array           $reports     A list of reports to print.
     * @param bool            $showSources TRUE if report should show error sources
     *                                     (not used by all reports).
     * @param string          $reportFile  A default file to log report output to.
     * @param int             $reportWidth How wide the screen reports should be.
     *
     * @return int The number of error and warning messages shown.
     */
    public function printErrorReport(PHP_CodeSniffer $phpcs, $reports, $showSources, $reportFile, $reportWidth)
    {
        $report = $phpcs->getFilesErrors();
        $this->report = $report;
        $_SESSION['current']['report'] = $report;

    }


    /**
     * Convert the passed standard into a valid standard.
     *
     * Checks things like default values and case.
     *
     * @param string $standard The standard to validate.
     *
     * @return string
     */
    public function validateStandard($standard)
    {
        if ($standard === null) {
            // They did not supply a standard to use.
            // Try to get the default from the config system.
            $standard = PHP_CodeSniffer::getConfigData('default_standard');
            if ($standard === null) {
                $standard = 'PEAR';
            }
        }

        // Check if the standard name is valid. If not, check that the case
        // was not entered incorrectly.

        if (PHP_CodeSniffer::isInstalledStandard($standard) === false) {
            $installedStandards = PHP_CodeSniffer::getInstalledStandards();
            foreach ($installedStandards as $validStandard) {
                if (strtolower($standard) === strtolower($validStandard)) {
                    $standard = $validStandard;
                    break;
                }
            }
        }

        return $standard;

    }//end validateStandard()
}//end class

?>
