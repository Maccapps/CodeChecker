<?php

class CodeCheck
{

    public $cfg = array();

    public $defaultStandard = 'default';

    public $projectsJsonFile = 'cfg/projects/';

    public $standardJsonFile = 'cfg/standards/';

    public $reportsJsonFile = 'cfg/reports/';

    public function __construct()
    {
        $this->fileServer = new FileServer();
        $this->sniffer = new Sniffer();

    }

    public function init()
    {
        $this->setDefaultSessionValues();
        $this->cfg['excludes'] = array();
        $this->cfg['excludes']['sniffs'] = array();
        $this->cfg['excludes']['standards'] = array();
        $this->cfg['excludes']['types'] = array();
        $this->cfg['extensions'] = array('php', 'js');

        $this->doSetValueRedirect();
        $this->setStandards();
        $this->setUserProjects();

    }

    public function setStandards()
    {
        $standards = array();
        $standardsJsonFiles = $this->fileServer->getFolderContents($this->standardJsonFile, 'file', 'json');

        foreach ($standardsJsonFiles as $file) {
            $standardFile = $this->standardJsonFile.$file['name'];
            $standard = json_decode(file_get_contents($standardFile), true);
            unset($standard['exclude']);
            $standard['file'] = CWD . '\\' . str_replace('/', '\\', $this->standardJsonFile.$file['name']);
            $standards[$file['filename']] = $standard;
        }
        $this->cfg['standards'] = $standards;

    }

    public function setUserProjects()
    {
        $projects = array();
        $projectsJsonFiles = $this->fileServer->getFolderContents($this->projectsJsonFile, 'file', 'json');

        foreach ($projectsJsonFiles as $file) {
            $project = json_decode(file_get_contents($this->projectsJsonFile.'/'.$file['name']), true);
            $project['file'] = $file['name'];
            $projects[] = $project;
        }

        foreach ($projects as $id => $project) {
            $projects[$id]['type'] = isset($projects[$id]['type']) ? $projects[$id]['type'] : $this->defaultStandard;
        }
        $this->cfg['projects'] = $projects;

    }

    public function setDefaultSessionValues()
    {
        if (!array_key_exists('current', $_SESSION)) {
            $_SESSION['current'] = array();
        }
        if (!array_key_exists('project', $_SESSION['current'])) {
            $_SESSION['current']['project'] = null;
        }
        if (!array_key_exists('folder', $_SESSION['current'])) {
            $_SESSION['current']['folder'] = null;
        }
        if (!array_key_exists('standard', $_SESSION['current'])) {
            $_SESSION['current']['standard'] = null;
        }
        if (!array_key_exists('action', $_SESSION['current'])) {
            $_SESSION['current']['action'] = 'projects';
        }

    }

    public function excludeStandard($standard)
    {
        $this->cfg['excludes']['standards'][] = $standard;

    }

    public function excludeType($type)
    {
        $this->cfg['excludes']['types'][] = $type;

    }

    public function excludeSniff($sniff)
    {
        $this->cfg['excludes']['sniffs'][] = $sniff;

    }

    public function doSetValueRedirect()
    {
        if (!array_key_exists('key', $_GET) OR $_GET['key'] == '' OR !array_key_exists('val', $_GET)) {
            return false;
        }
        $key = $_GET['key'];
        $val = $_GET['val'];
        switch($key) {

            case 'action':
                $_SESSION['current']['action'] = $_GET['val'];
                header("location: /");
                break;

            case 'file':
                $_SESSION['current']['file'] = $_GET['val'];
                $_SESSION['current']['action'] = 'check';
                header("location: /");
                break;

            case 'folder':
                $_SESSION['current']['folder'] = $_GET['val'];
                $_SESSION['current']['parent'] = $this->fileServer->getParentFolder($_GET['val']);
                $_SESSION['current']['file'] = null;
                $_SESSION['current']['action'] = 'list';
                header("location: /");
                break;

            case 'history':
                $parts = explode('___', $_GET['val']);
                $_SESSION['current']['file'] = $parts[1];
                $_SESSION['current']['action'] = 'check';
                $_SESSION['current']['folder'] = $parts[0];
                $_SESSION['current']['parent'] = $this->fileServer->getParentFolder($parts[0]);
                header("location: /");
                break;

            case 'project':
                $_SESSION['current']['project'] = $_GET['val'];
                $_SESSION['current']['folder'] = null;
                $_SESSION['current']['file'] = null;
                $_SESSION['current']['standard'] = isset($cfg['projects'][$_GET['val']]['type']) ? $cfg['projects'][$_GET['val']]['type'] : null;
                $_SESSION['current']['action'] = 'list';
                header("location: /");
                break;

        }

    }

    public function outputCurrentPath($folder, $project)
    {
        echo '<p class="table-current-path"> ';
        $relative_folder = str_replace($project['base'], '', $folder);
        if ($relative_folder == '') {
            echo '\\';
        } else {
            $folders = array_filter(explode('\\', $relative_folder));
            $path = $project['base'];
            foreach ($folders as $folder) {
                $path .= '\\' . $folder;
                echo ' \\ <a href="?key=folder&val='.$path.'">' . $folder . '</a>';
            }
        }
        echo '</p>';

    }

    public function outputFolderContents($folder, $project, $contents)
    {
        echo '<table cellspacing="0" width="100%" >' . "\n";
        echo '<tr class="header">';
        echo '<td style="width:22px !important;">&nbsp;</td>';
        echo '<td class="col right-divider">Filename</td>';
        echo '<td class="col-hash right-divider" style="width:62px !important;">Hash</td>';
        echo '<td class="col-errors right-divider" style="width:18px !important;">E</td>';
        echo '<td class="col-warnings right-divider" style="width:18px !important;">W</td>';
        echo '<td class="col-security" style="width:18px !important;">S</td>';
        echo '</tr>' . "\n";

        if ($folder != $project['base']) {
            echo '<tr>';
            echo '<td class="icon icon--folder"></td>';
            echo '<td><a href="?key=folder&val='.$_SESSION['current']['parent'].'">..</a></td>';
            echo '<td colspan="3">&nbsp;</td>';
            echo '</tr>' . "\n";
        }

        $rows = 0;
        //  output folders
        foreach ($contents as $item) {
            if ($item['type'] == 'directory') {
                echo '<tr>';
                echo '<td class="icon icon--folder"></td>';
                echo '<td class="col-directory"><a href="?key=folder&val='.$folder.'\\'.$item['name'].'">'.$item['name'].'</a></td>';
                echo '<td colspan="3">&nbsp;</td>';
                echo '</tr>' . "\n";
                $rows++;
            }
            if ($item['type'] == 'file' AND substr($item['name'], strlen($item['name']) - 6) == 'min.js') {
                continue;
            }

            if ($item['type'] == 'file' AND in_array($item['extension'], $this->cfg['extensions'])) {
                $filepath = $folder.DIRECTORY_SEPARATOR.$item['name'];
                $contentsHash = md5(file_get_contents($filepath));
                $hashfilename = str_replace($project['base'].'\\', '', $filepath);
                $reportsFile = CWD.'/'.$this->reportsJsonFile . str_replace('.json', '', $project['file']).'/'.md5($hashfilename).'.json';
                $markerClasses = array();
                $type = 'errors found';
                if (!file_exists($reportsFile)) {
                    $type = 'not checked';
                } else {
                    $report = json_decode(file_get_contents($reportsFile), true);
                    if ($report['hash'] !== $contentsHash) {
                        $type = 'out of date';
                    } else {
                        if ($report['errors'] == 0 AND $report['warnings'] == 0 AND $report['security'] == 0) {
                            $type = 'all good';
                        }
                        $markerClasses['errors'] = $report['errors'] > 0 ? 'bad' : 'good';
                        $markerClasses['warnings'] = $report['warnings'] > 0 ? 'bad' : 'good';
                        $markerClasses['security'] = $report['security'] > 0 ? 'bad' : 'good';
                        $markerCounts['errors'] = $report['errors'] > 9 ? '>' : $report['errors'];
                        $markerCounts['warnings'] = $report['warnings'] > 9 ? '>' : $report['warnings'];
                        $markerCounts['security'] = $report['security'] > 9 ? '>' : $report['security'];
                        $markerCounts['errors'] = $report['errors'] == 0 ? '&nbsp;' : $markerCounts['errors'];
                        $markerCounts['warnings'] = $report['warnings'] == 0 ? '&nbsp;' : $markerCounts['warnings'];
                        $markerCounts['security'] = $report['security'] == 0 ? '&nbsp;' : $markerCounts['security'];
                    }
                }
                $classes = array();
                if ($item['name'] === $_SESSION['current']['file']) {
                    $classes[] = 'active';
                }
                echo '<tr class="'.implode(' ', $classes).'">';
                echo '<td class="icon icon--file"></td>';
                echo '<td class="col-file"><a href="?key=file&val='.$item['name'].'" title="'.$reportsFile.'">'.$item['name'].'</a></td>';
                switch($type) {
                    case 'all good':
                        echo '<td colspan="3" class="cols-all-good"><span>all good</span></td>';
                        break;
                    case 'not checked':
                        echo '<td colspan="3" class="cols-not-checked"><span>not checked</span></td>';
                        break;
                    case 'out of date':
                        echo '<td colspan="3" class="cols-out-of-date"><span>out of date</span></td>';
                        break;
                    case 'errors found':
                        echo '<td class="col-errors"><div class="marker '.$markerClasses['errors'].'" title="'.$report['errors'].'">'.$markerCounts['errors'].'</div></td>';
                        echo '<td class="col-warnings"><div class="marker '.$markerClasses['warnings'].'" title="'.$report['warnings'].'">'.$markerCounts['warnings'].'</div></td>';
                        echo '<td class="col-security"><div class="marker '.$markerClasses['security'].'" title="'.$report['security'].'">'.$markerCounts['security'].'</div></td>';
                        break;
                }

                echo '</tr>' . "\n";
                $rows++;
            }
        }
        echo '</table>' . "\n";

    }

    public function outputFileIssues($folder, $project, $issues)
    {
        ?>

        <table cellspacing="0" width="100%" id="table-results">
        <tbody>
        <tr>
            <td colspan="2">&nbsp;</td>
        </tr>
        <?php

        #echo '<pre>' . var_export($issues, TRUE) . '</pre>'; die('<pre>Exit at Line '.__LINE__.' of <span title="'.__FILE__.'">'.str_replace(array(__DIR__, '\\'), '', __FILE__).'</span> @ '.date('H:i:s'));

        if (array_key_exists('errors', $issues)) {
            foreach ($issues['errors'] as $item) {
                ?>
                <tr class="hide issue jsToggleWrapper" data-type="errors">
                    <td class="col col-line" title="Column <?php echo $item['char']; ?>"><?php echo $item['line']; ?></td>
                    <td class="col col-issue" title="<?php echo $item['src']; ?>"><?php echo $item['issue']; ?>
                        <?php if (isset($item['src'])) { ?>
                        <span class="twister jsToggleTrigger"></span>
                        <?php $bits = explode('.', $item['src']); array_pop($bits); $details = implode(' ', $bits); ?>
                        <pre class="issue-info hide jsToggleTarget"><?php echo $details; ?></pre>
                        <?php } ?>
                    </td>
                </tr>
                <?php
            }
        } else {
            ?>
            <tr class="hide issue issue--none" data-type="errors">
                <td>There are no errors.</td>
            </tr>
            <?php
        }

        if (array_key_exists('warnings', $issues)) {
            foreach ($issues['warnings'] as $item) {
            ?>
            <tr class="hide issue jsToggleWrapper" data-type="warnings">
                <td class="col col-line" title="Column <?php echo $item['char']; ?>"><?php echo $item['line']; ?></td>
                <td class="col col-issue" title="<?php echo $item['src']; ?>"><?php echo $item['issue']; ?>
                    <?php if (isset($item['src'])) { ?>
                    <span class="twister jsToggleTrigger"></span>
                    <?php $bits = explode('.', $item['src']); array_pop($bits); $details = implode(' ', $bits); ?>
                    <pre class="issue-info hide jsToggleTarget"><?php echo $details; ?></pre>
                    <?php } ?>
                </td>
            </tr>
            <?php
            }
        } else {
            ?>
            <tr class="hide issue issue--none" data-type="warnings">
                <td>There are no warnings.</td>
            </tr>
            <?php
        }

        if (array_key_exists('security', $issues)) {
            foreach ($issues['security'] as $item) {
            ?>
            <tr class="hide issue jsToggleWrapper" data-type="security">
                <td class="col col-line" title="Column <?php echo $item['char']; ?>"><?php echo $item['line']; ?></td>
                <td class="col col-issue" title="<?php echo $item['src']; ?>"><?php echo $item['issue']; ?>
                <span class="twister jsToggleTrigger"></span>
                <?php
                $lines = array();
                $item['trace'] = array_reverse($item['trace']);
                foreach($item['trace'] as $line) {
                    $lines[] = $line;
                }
                echo '<pre class="issue-info hide jsToggleTarget">' . implode('<br/>', $lines) . '</pre>';
                ?>
                </td>
            </tr>
            <?php
            }
        } else {
            ?>
            <tr class="hide issue issue--none" data-type="security">
                <td>There are no security issues.</td>
            </tr>
            <?php
        }
        ?>
        </tbody>
        </table>
            <?php
            #echo '<pre>' . var_export($issues, TRUE) . '</pre>';die('<pre>Exit at Line '.__LINE__.' of <span title="'.__FILE__.'">'.str_replace(array(__DIR__, '\\'), '', __FILE__).'</span> @ '.date('H:i:s'));
            $iconType = 'good';
            if (count($issues) > 0) {
                if ($issues['warnings'] > 0) {
                    $iconType = 'warnings';
                }
                if ($issues['errors'] > 0) {
                    $iconType = 'errors';
                }
                if ($issues['security'] > 0) {
                    $iconType = 'security';
                }
            }
            $this->updateIcon($iconType);
}

    public function outputFileIssueHeader($folder, $project, $issues, $file)
    {
        $activeTab = null;
        $activeTab = ($activeTab === null AND count($issues['errors']) > 0) ? 'errors' : $activeTab;
        $activeTab = ($activeTab === null AND count($issues['warnings']) > 0) ? 'warnings' : $activeTab;
        $activeTab = ($activeTab === null AND count($issues['security']) > 0) ? 'security' : $activeTab;

        echo '<p class="table-counts">';
        $items = array();
        $item = '<span data-tab-id="errors" class="';
        $item .= $activeTab === 'errors' ? 'active' : '';
        $item .= '">Errors ['.count($issues['errors']).']</span>';
        $items[] = $item;

        $item = '<span data-tab-id="warnings" class="';
        $item .= $activeTab === 'warnings' ? 'active' : '';
        $item .= '">Warnings ['.count($issues['warnings']).']</span>';
        $items[] = $item;

        $item = '<span data-tab-id="security" class="';
        $item .= $activeTab === 'security' ? 'active' : '';
        $item .= '">Security ['.count($issues['security']).']</span>';
        $items[] = $item;

        #$item = '<a href="?edit=explorer+'.urlencode($file).'" class="edit-file">Edit File</a>';
        #$items[] = $item;

        echo implode(' &nbsp; ', $items);
        echo '</p>';
    }

    function updateIcon($type)
    {
        $icons['good'] = '090';
        $icons['errors'] = 'C36';
        $icons['warnings'] = 'FC0';
        $icons['security'] = '39F';

        $icoName = $icons[$type];
    ?>
    <script>
    (function() {
        var link = document.createElement('link');
        link.type = 'image/x-icon';
        link.rel = 'shortcut icon';
        link.href = '_global/img/circle-16-<?php echo $icoName; ?>.png';
        document.getElementsByTagName('head')[0].appendChild(link);
    }());
    </script>
    <?php

    }

    function formatJsLintResults($results, $file)
    {
        $_SESSION['ignoreStart'] = null;
        $ignoreLines = array();

        $lines = file($file);
        foreach ($lines as $line_num => $line) {
            if (strpos(strtolower($line), '//  ccignoreline') > -1) {
                $ignoreLines[] = $line_num + 1;
            }
            if (strpos(strtolower($line), '//  ccignorestart') > -1) {
                $_SESSION['ignoreStart'] = $line_num + 1;
            }
            if ($_SESSION['ignoreStart'] !== null AND (strpos(strtolower($line), '//  ccignoreend') > -1 OR $line_num == count($lines) - 1)) {
                for($lin = $_SESSION['ignoreStart']; $lin <= $line_num; $lin++) {
                    $ignoreLines[] = $lin;
                }
                $_SESSION['ignoreStart'] = null;
            }
        }

        $results = $results === null ? [] : $results;
        $errors = array();
        foreach($results as $result) {
            $error['line'] = $result['line'];
            $error['char'] = $result['col'];
            $error['issue'] = $result['reason'];
            if (!in_array($error['line'], $ignoreLines)) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    function getHistoryContents($folder, $project)
    {
        $projectReportDir = $this->reportsJsonFile . str_replace('.json', '', $project['file']);
        $historyFile = $projectReportDir . '/_history.json';

        if (file_exists($historyFile)) {
            $history = json_decode(file_get_contents($historyFile), true);
        } else {
            $history = array();
        }
        return $history;

    }

    function outputHistoryContents($folder, $project, $contents)
    {
        $base = $this->cfg['projects'][$_SESSION['current']['project']]['base'];

        if (count($contents) < 1) {
            return false;
        }
        echo '<table cellspacing="0" width="100%" >' . "\n";
        echo '<tr class="header">';
        echo '<td style="width:22px !important;">&nbsp;</td>';
        echo '<td class="col right-divider">Filename</td>';
        echo '<td class="col-hash right-divider" style="width:62px !important;">Hash</td>';
        echo '<td class="col-errors right-divider" style="width:18px !important;">E</td>';
        echo '<td class="col-warnings right-divider" style="width:18px !important;">W</td>';
        echo '<td class="col-security" style="width:18px !important;">S</td>';
        echo '</tr>' . "\n";
        echo '<p> &nbsp; </p>' . "\n";
        $rows = 0;
        //  output folders
        foreach ($contents as $hashfilename => $item) {

            if (substr($item['folder'], 0, strlen($folder)) !== $folder) {
                continue;
            }

            $filepath = $item['folder'] . '\\' . $item['file'];
            $contentsHash = md5(file_get_contents($filepath));
            #$hashfilename = str_replace($project['base'].'\\', '', $filepath);
            $reportsFile = CWD.'/'.$this->reportsJsonFile . str_replace('.json', '', $project['file']).'/'.$hashfilename.'.json';

            $type = 'errors found';
     
            $report = json_decode(file_get_contents($reportsFile), true);
            if ($report['hash'] !== $contentsHash) {
                $type = 'out of date';
            } else {
                if ($report['errors'] == 0 AND $report['warnings'] == 0 AND $report['security'] == 0) {
                    $type = 'all good';
                }
                $markerClasses['errors'] = $report['errors'] > 0 ? 'bad' : 'good';
                $markerClasses['warnings'] = $report['warnings'] > 0 ? 'bad' : 'good';
                $markerClasses['security'] = $report['security'] > 0 ? 'bad' : 'good';
                $markerCounts['errors'] = $report['errors'] > 9 ? '>' : $report['errors'];
                $markerCounts['warnings'] = $report['warnings'] > 9 ? '>' : $report['warnings'];
                $markerCounts['security'] = $report['security'] > 9 ? '>' : $report['security'];
                $markerCounts['errors'] = $report['errors'] == 0 ? '&nbsp;' : $markerCounts['errors'];
                $markerCounts['warnings'] = $report['warnings'] == 0 ? '&nbsp;' : $markerCounts['warnings'];
                $markerCounts['security'] = $report['security'] == 0 ? '&nbsp;' : $markerCounts['security'];
            }


            $classes = array('active');
            // if ($item['file'] === $_SESSION['current']['file']) {
                // $classes[] = 'active';
            // }
            echo '<tr class="'.implode(' ', $classes).'">';
            echo '<td class="icon icon--file"></td>';
            $filename = str_replace('\\', '<span class="screen-l"> </span>\\<span class="screen-l"> </span>', str_replace($base, '', $item['folder'])). ' \ ' . $item['file'];
            echo '<td class="col-file"><a href="?key=history&val='.$item['folder'].'___'.$item['file'].'" title="'.$reportsFile.'">'. $filename . '</a></td>';
            switch($type) {
                case 'all good':
                    echo '<td colspan="3" class="cols-all-good"><span>all good</span></td>';
                    break;
                case 'not checked':
                    echo '<td colspan="3" class="cols-not-checked"><span>not checked</span></td>';
                    break;
                case 'out of date':
                    echo '<td colspan="3" class="cols-out-of-date"><span>out of date</span></td>';
                    break;
                case 'errors found':
                    echo '<td class="col-errors"><div class="marker '.$markerClasses['errors'].'" title="'.$report['errors'].'">'.$markerCounts['errors'].'</div></td>';
                    echo '<td class="col-warnings"><div class="marker '.$markerClasses['warnings'].'" title="'.$report['warnings'].'">'.$markerCounts['warnings'].'</div></td>';
                    echo '<td class="col-security"><div class="marker '.$markerClasses['security'].'" title="'.$report['security'].'">'.$markerCounts['security'].'</div></td>';
                    break;
            }

            echo '</tr>' . "\n";
            $rows++;

        }
        echo '</table>' . "\n";

    }

}