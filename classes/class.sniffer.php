<?php
require_once 'CodeSniffer/CLI.php';

class Sniffer
{

    public function __construct()
    {
        $this->phpcs = new PHP_CodeSniffer_CLI();
        $this->phpcs->checkRequirements();

    }

    public function getFormattedResults($file, $standard, $fileExt)
    {
        switch($fileExt) {
            case 'js':
            $file = str_replace('\\', '/', $file);
            $report = $this->runJSReport($file, $standard);
            break;

            default:
            $report = $this->runDefaultReport($file, $standard);
            break;
    
        }

        return $report;

    }

    public function runJSReport($file, $standard)
    {  
         ?>
                <div class="report_filename hide"><p></p><input type="image" class="submit_back sniff-refresh" onclick="location.reload();"></div><div class="report_summary"> &nbsp; </div></pre></div>
                <script src="jslint/web_jslint.js"></script>

                <script src="_global/js/vendor/jquery/1.12.4/jquery.min.js"></script>
                <script src="jslint/lint_remote_file.js"></script>

                <div id="JSLINT_" style="display:none;">
                    <div id=JSLINT_EDITION></div>
                    <div id=JSLINT_SOURCE><textarea></textarea></div>
                    <div id=JSLINT_BUTTON></div>
                    <div id=JSLINT_ERRORS></div>
                    <div id=JSLINT_REPORT></div>
                    <div id=JSLINT_PROPERTIES><textarea></textarea></div>
                    <div id=JSLINT_OPTIONS></div>
                    <input id=JSLINT_INDENT>
                    <input id=JSLINT_MAXLEN>
                    <input id=JSLINT_MAXERR>
                    <textarea id=JSLINT_PREDEF></textarea>
                    <div id=JSLINT_JSLINT><textarea></textarea></div>
                    <script>ADSAFE.id("JSLINT_");</script>
                    <script src="jslint/init_ui.js"></script>
                    <script>
                    ADSAFE.go("JSLINT_", function (dom, lib) {
                        'use strict';
                        lib.init_ui(dom);
                        <?php
                        echo 'lintExternalFile(lib, "'.$file.'");'."\n";
                        ?>
                    });
                    </script>
                </div>

            <?php
        return $report;
    }

    public function runDefaultReport($file, $standard)
    {  
        $this->runReport($file, $standard);
        $report = $this->phpcs->report;
        $report = $this->formatReport(array_values($report)[0]);
        return $report;
    }

    public function runReport($file, $standard)
    {
        $this->loadAllSniffs();
        if ($standard !== null) {
            $this->excludeStandardSniffs($standard);
        }

        $_SERVER['argc'] = 3;
        $standard = '--standard=PSR2';
        $_SERVER['argv'] = array('phpcs.php', $standard, $file);
        $this->phpcs->process();

    }

    public function formatReport($report)
    {
        $newData = array(
            'errors' => $report['errors'],
            'warnings' => $report['warnings']
        );
        ksort($newData['errors']);
        ksort($newData['warnings']);

        $issuesFound = array();
        require_once '../cfg/standards/settings.php';

        $newReport = array();
        foreach ($newData as $type => $typeData) {
            foreach ($typeData as $line => $lineData) {
                foreach ($lineData as $char => $charData) {
                    $thisLineCharIssues = array();
                    foreach ($charData as $issue) {
                        if (in_array($issue['source'], $cfg['onlyShowOneResultForTheseSniffs']) AND in_array($issue['source'], $issuesFound)) {
                            break;
                        }
                        if (!in_array($issue['message'], $thisLineCharIssues)) {
                            $thisLineCharIssues[] = $issue['message'];
                            $newReport[$type][] = array(
                                'line' => $line,
                                'char' => $char,
                                'issue' => $issue['message'],
                                'src' => $issue['source'],
                            );
                            $issuesFound[] = $issue['source'];
                        }
                    }
                }
            }
        }
        return $newReport;

    }

    public function loadAllSniffs()
    {
        $sniffs = array();
        $checkForDuplicates = array();
        $duplicates = 1;

        $folder = 'CodeSniffer/Standards';
        $standards = $this->getFolderContents($folder, 'directory');
        foreach ($standards as $standard) {
            if (substr($standard['name'], 0, 3) === 'zzz') {
                continue;
            }
            $folder = 'CodeSniffer/Standards/'  .$standard['name'] . '/Sniffs';
            $sections = $this->getFolderContents($folder, 'directory');
            foreach ($sections as $section) {
                $folder = 'CodeSniffer/Standards/' . $standard['name'] . '/Sniffs/' . $section['name'];
                $files = $this->getFolderContents($folder, 'file');
                foreach ($files as $file) {
                    $sniffs[] = $standard['name'] . '_Sniffs_' . $section['name'] . '_' . $file['filename'];
                    if (array_key_exists($section['name'] . '_' . $file['filename'], $checkForDuplicates)) {
                        #echo "<br/>$duplicates - Warning: " . $section['name'] . '_' . $file['filename'] . " duplicated: " . $standard['name'] . ' > ' . $checkForDuplicates[$section['name'] . '_' . $file['filename']];
                        $duplicates++;
                    }
                    $checkForDuplicates[$section['name'] . '_' . $file['filename']] = $standard['name'];
                }
            }
        }
        $GLOBALS['cfg']['sniffs'] = $sniffs;

    }

    public function excludeStandardSniffs($standard)
    {
        $standards_file = 'cfg/standards/' . $standard . '.json';

        if (!file_exists($standards_file)) {
            echo "Standards file not found for: " . $standard;
        } else {
            $standardData = json_decode(file_get_contents($standards_file), true);

            $exclusionStarts = array();
            foreach ($standardData['exclude'] as $typeName => $typeData) {
                if ($typeData === null) {
                    $exclusionStarts[] = $typeName;
                    continue;
                }
                foreach ($typeData as $categoryName => $categoryData) {
                    if ($categoryData === null) {
                        $exclusionStarts[] = $typeName . '_Sniffs_' . $categoryName;
                        continue;
                    }
                    foreach ($categoryData as $sniffName) {
                        $exclusionStarts[] = $typeName . '_Sniffs_' . $categoryName . '_' . $sniffName . 'Sniff';
                    }
                }
            }
            foreach ($exclusionStarts as $exclusionStart) {
                foreach ($GLOBALS['cfg']['sniffs'] as $id => $name) {
                    if ($exclusionStart === substr($name, 0, strlen($exclusionStart))) {
                        unset($GLOBALS['cfg']['sniffs'][$id]);
                    }
                }
            }
        }

    }

    public function getFolderContents($folder, $type = null)
    {
        if ($handle = opendir($folder)) {

            $contents = array();

            while (false !== ($item = readdir($handle))) {
                if ($item != '.' AND $item != '..') {
                    $filepath = $folder.DIRECTORY_SEPARATOR.$item;
                    $info = pathinfo($filepath);
                    $data['type'] = is_dir($filepath) ? 'directory' : 'file';
                    $data['name'] = $item;
                    if ($data['type'] === 'file') {
                        $data['extension'] = $info['extension'];
                        $data['filename'] = $info['filename'];
                        $data['filesize'] = filesize($filepath);
                    }
                    if ($data['type'] === 'file' AND in_array(strtoupper($data['extension']), array('BMP', 'GIF', 'JPG', 'JPEG', 'PNG'))) {
                        $image_info = getimagesize($filepath);
                        $data['image_width'] = $image_info[0];
                        $data['image_height'] = $image_info[1];
                        $data['image_mime'] = $image_info['mime'];
                    }
                    $contents[] = $data;
                }
            }

            closedir($handle);
        }

        if ($type === null) {
            return $contents;
        } else {
            $filtered = array();
            foreach ($contents as $content) {
                if ($content['type'] === $type) {
                    $filtered[] = $content;
                }
            }
            return $filtered;

        }

    }
}
?>