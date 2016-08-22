<?php

error_reporting(E_ALL ^ E_DEPRECATED ^ E_STRICT);  //  E_ERROR, E_WARNING, E_PARSE, E_NOTICE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_STRICT, E_RECOVERABLE_ERROR, E_DEPRECATED, E_USER_DEPRECATED, E_ALL
function getSecurityReport($file, $report)
{
    global $F_XSS;
    global $NAME_XSS;
    global $F_HTTP_HEADER;
    global $NAME_HTTP_HEADER;	
    global $F_SESSION_FIXATION;
    global $NAME_SESSION_FIXATION;
    global $F_DATABASE;
    global $NAME_DATABASE;	
    global $F_FILE_READ;
    global $NAME_FILE_READ;
    global $F_FILE_AFFECT;
    global $NAME_FILE_AFFECT;
    global $F_FILE_INCLUDE;
    global $NAME_FILE_INCLUDE;	
    global $F_CONNECT;
    global $NAME_CONNECT;		
    global $F_EXEC;
    global $NAME_EXEC;
    global $F_CODE;
    global $NAME_CODE;
    global $F_REFLECTION;
    global $NAME_REFLECTION;
    global $F_XPATH;
    global $NAME_XPATH;
    global $F_LDAP;
    global $NAME_LDAP;
    global $F_POP;
    global $NAME_POP;
    global $F_OTHER;
    global $NAME_OTHER;

    $_POST['loc'] = $file;
    $_POST['vector'] = 'all';


function getWhatIWant($output)
{
    $output = json_decode(json_encode($output), true);

    $report = array();
    foreach($output as $file => $items) {
        foreach($items as $id => $item) {
            #echo '<pre>' . var_export($item, TRUE) . '</pre>';die('<pre>Exit at Line '.__LINE__.' of <span title="'.__FILE__.'">'.str_replace(array(__DIR__, '\\'), '', __FILE__).'</span> @ '.date('H:i:s'));
            $thisReportItem = array();
            $thisReportItem['issue'] = $item['category'];
            $thisReportItem['line'] = $item['treenodes'][0]['lines'][0];
            $thisReportItem['trace'][$thisReportItem['line']] = str_replace('&nbsp;', ' ', strip_tags($item['treenodes'][0]['value']));

            $root = $item['treenodes'][0];
            while(isset($root['children'][0]['line'])) {
                $thisReportItem['trace'][$root['children'][0]['line']] = str_replace('&nbsp;', ' ', strip_tags($root['children'][0]['value']));
                $root = $root['children'][0];
            };

            $report[] = $thisReportItem;
        }
    }

    #echo '<pre>' . var_export($report, TRUE) . '</pre>';die('<pre>Exit at Line '.__LINE__.' of <span title="'.__FILE__.'">'.str_replace(array(__DIR__, '\\'), '', __FILE__).'</span> @ '.date('H:i:s'));
    #echo '<pre>' . var_export($output, TRUE) . '</pre>';
    return $report;

}

/** 

RIPS - A static source code analyser for vulnerabilities in PHP scripts 
    by Johannes Dahse (johannes.dahse@rub.de)
            

Copyright (C) 2012 Johannes Dahse

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>.        

**/

    ###############################  INCLUDES  ################################

    require_once('../RIPS/config/general.php');            // general settings
    require_once('../RIPS/config/sources.php');            // tainted variables and functions
    require_once('../RIPS/config/tokens.php');            // tokens for lexical analysis
    require_once('../RIPS/config/securing.php');            // securing functions
    require_once('../RIPS/config/sinks.php');            // sensitive sinks
    require_once('../RIPS/config/info.php');                // interesting functions
    
    require_once('../RIPS/lib/constructer.php');         // classes    
    require_once('../RIPS/lib/filer.php');                // read files from dirs and subdirs
    require_once('../RIPS/lib/tokenizer.php');            // prepare and fix token list
    require_once('../RIPS/lib/analyzer.php');            // string analyzers
    require_once('../RIPS/lib/scanner.php');                // provides class for scan
    require_once('../RIPS/lib/printer.php');                // output scan result
    require_once('../RIPS/lib/searcher.php');            // search functions
        
    require_once('../RIPS/_functions.php');
    ###############################  MAIN  ####################################
    
    $start = microtime(TRUE);
    
    $output = array();
    $info = array();
    $scanned_files = array();
    
    if(!empty($_POST['loc']))
    {        

        $location = realpath($_POST['loc']);

        if(is_dir($location))
        {
            $scan_subdirs = isset($_POST['subdirs']) ? $_POST['subdirs'] : false;
            $files = read_recursiv($location, $scan_subdirs);
            
            if(count($files) > WARNFILES && !isset($_POST['ignore_warning']))
                die('warning:'.count($files));
        }    
        else if(is_file($location) && in_array(substr($location, strrpos($location, '.')), $FILETYPES))
        {
            $files[0] = $location;
        }
        else
        {
            $files = array();
        }
        

        // SCAN
        if(empty($_POST['search']))
        {
            $user_functions = array();
            $user_functions_offset = array();
            $user_input = array();
            
            $file_sinks_count = array();
            $count_xss=$count_sqli=$count_fr=$count_fa=$count_fi=$count_exec=$count_code=$count_eval=$count_xpath=$count_ldap=$count_con=$count_other=$count_pop=$count_inc=$count_inc_fail=$count_header=$count_sf=$count_ri=0;
            
            $verbosity = isset($_POST['verbosity']) ? $_POST['verbosity'] : 1;
            $scan_functions = array();
            $info_functions = Info::$F_INTEREST;
            
            if($verbosity != 5)
            {
                switch($_POST['vector']) 
                {
                    case 'xss':            $scan_functions = $F_XSS;            break;
                    case 'httpheader':    $scan_functions = $F_HTTP_HEADER;    break;
                    case 'fixation':    $scan_functions = $F_SESSION_FIXATION;    break;
                    case 'code':         $scan_functions = $F_CODE;            break;
                    case 'ri':             $scan_functions = $F_REFLECTION;    break;
                    case 'file_read':    $scan_functions = $F_FILE_READ;        break;
                    case 'file_affect':    $scan_functions = $F_FILE_AFFECT;    break;        
                    case 'file_include':$scan_functions = $F_FILE_INCLUDE;    break;            
                    case 'exec':          $scan_functions = $F_EXEC;            break;
                    case 'database':     $scan_functions = $F_DATABASE;        break;
                    case 'xpath':        $scan_functions = $F_XPATH;            break;
                    case 'ldap':        $scan_functions = $F_LDAP;            break;
                    case 'connect':     $scan_functions = $F_CONNECT;        break;
                    case 'other':        $scan_functions = $F_OTHER;            break;
                    case 'unserialize':    {
                                        $scan_functions = $F_POP;                
                                        $info_functions = Info::$F_INTEREST_POP;
                                        $source_functions = array('unserialize');
                                        $verbosity = 2;
                                        } 
                                        break;
                    case 'client':
                        $scan_functions = array_merge(
                            $F_XSS,
                            $F_HTTP_HEADER,
                            $F_SESSION_FIXATION
                        );
                        break;
                    case 'server': 
                        $scan_functions = array_merge(
                            $F_CODE,
                            $F_REFLECTION,
                            $F_FILE_READ,
                            $F_FILE_AFFECT,
                            $F_FILE_INCLUDE,
                            $F_EXEC,
                            $F_DATABASE,
                            $F_XPATH,
                            $F_LDAP,
                            $F_CONNECT,
                            $F_POP,
                            $F_OTHER
                        ); break;    
                    case 'all': 
                    default:
                        $scan_functions = array_merge(
                            $F_XSS,
                            $F_HTTP_HEADER,
                            $F_SESSION_FIXATION,
                            $F_CODE,
                            $F_REFLECTION,
                            $F_FILE_READ,
                            $F_FILE_AFFECT,
                            $F_FILE_INCLUDE,
                            $F_EXEC,
                            $F_DATABASE,
                            $F_XPATH,
                            $F_LDAP,
                            $F_CONNECT,
                            $F_POP,
                            $F_OTHER
                        ); break;
                }
            }    

            if($_POST['vector'] !== 'unserialize')
            {
                $source_functions = Sources::$F_OTHER_INPUT;
                // add file and database functions as tainting functions
                if( $verbosity > 1 && $verbosity < 5 )
                {
                    $source_functions = array_merge(Sources::$F_OTHER_INPUT, Sources::$F_FILE_INPUT, Sources::$F_DATABASE_INPUT);
                }
            }    
                    
            $overall_time = 0;
            $timeleft = 0;
            $file_amount = count($files);        
            for($fit=0; $fit<$file_amount; $fit++)
            {
                // for scanning display
                $thisfile_start = microtime(TRUE);
                $file_scanning = $files[$fit];
                
                #echo ($fit) . '|' . $file_amount . '|' . $file_scanning . '|' . $timeleft . '|' . "\n";
                @ob_flush();
                flush();
    
                // scan
                $scan = new Scanner($file_scanning, $scan_functions, $info_functions, $source_functions);
                $root = dirname(__FILE__);
                $root = substr($root, 0, strlen($root)-3);

                $scan->parse();


            }
            #die("done");
            #echo "STATS_DONE.\n";
            @ob_flush();
            flush();

            $myreport = getWhatIWant($GLOBALS['output']);
            return $myreport;
        }
    } 

}

?>