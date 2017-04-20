<?php

class FileServer
{

    public function __construct()
    {

    }

    public function getFolderContents($folder, $typeToShow = null, $fileExtToShow = null)
    {
        $contents = array();
        if ($handle = opendir($folder)) {

            while (false !== ($item = readdir($handle))) {
                if ($item != '.' AND $item != '..') {
                    $filepath = $folder . DIRECTORY_SEPARATOR . $item;
                    $info = pathinfo($filepath);
                    $data['type'] = is_dir($filepath) ? 'directory' : 'file';
                    $data['name'] = $item;
                    if ($data['type'] === 'file' AND isset($info['extension'])) {
                        $data['extension'] = $info['extension'];
                        $data['filename'] = $info['filename'];
                        $data['filesize'] = filesize($filepath);
                    }
                    if ($typeToShow === null OR $typeToShow === $data['type']) {
                        if ($fileExtToShow === null) {
                            $contents[] = $data;
                        } else {
                            if (isset($data['extension']) AND $fileExtToShow === $data['extension']) {
                                $contents[] = $data;
                            }
                        }
                    }
                }
            }

            closedir($handle);
        }

        $contents = $this->sortFolderContents($contents);

        return $contents;

    }

    public function sortFolderContents($contents)
    {
        $addedItems = array();
        $newOrder = array();

        foreach ($contents as $item) {
            $itemRef = $item['type'].$item['name'];
            if ($item['type'] === 'directory') {
                if (!in_array($itemRef, $addedItems) AND !preg_match('/^[A-Za-z0-9]+$/', $item['name'][0])) {
                    $newOrder[] = $item;
                    $addedItems[] = $itemRef;
                }
            }
        }
        foreach ($contents as $item) {
            $itemRef = $item['type'].$item['name'];
            if ($item['type'] === 'directory') {
                if (!in_array($itemRef, $addedItems)) {
                    $newOrder[] = $item;
                }
            }
        }
        foreach ($contents as $item) {
            $itemRef = $item['type'].$item['name'];
            if ($item['type'] === 'file') {
                if (!in_array($itemRef, $addedItems) AND !preg_match('/^[A-Za-z0-9]+$/', $item['name'][0])) {
                    $newOrder[] = $item;
                    $addedItems[] = $itemRef;
                }
            }
        }
        foreach ($contents as $item) {
            $itemRef = $item['type'].$item['name'];
            if ($item['type'] === 'file') {
                if (!in_array($itemRef, $addedItems)) {
                    $newOrder[] = $item;
                }
            }
        }

        return $newOrder;

    }

    public function getParentFolder($folder)
    {
        return substr($folder, 0, strrpos($folder, '\\'));

    }

    public function arraySortByField($aArrayToSort, $sKeyToSort, $sDir = 'a', $bIsNumber = false)
    {
        foreach ($aArrayToSort as $key => $data) {
            if (!array_key_exists($sKeyToSort, $aArrayToSort[$key])) {
                return $aArrayToSort;
            }
            $aArrayTmp[$key] = $aArrayToSort[$key][$sKeyToSort] . '----' . $key;
        }
        if ($bIsNumber == false) {
            if ($sDir == 'a') {
                asort($aArrayTmp);
            } else {
                rsort($aArrayTmp);
            }
        } else {
            natsort($aArrayTmp);
            if ($sDir != 'a') {
                $aArrayTmp = array_reverse($aArrayTmp);
            }
        }

        foreach ($aArrayTmp as $key => $val) {
            $aArrayToSortTmp = explode('----', $val);
            $aSortedArray[$aArrayToSortTmp[1]] = $aArrayToSort[$aArrayToSortTmp[1]];
        }
        return $aSortedArray;

    }

}

?>