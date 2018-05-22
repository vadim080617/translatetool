<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Chumper\Zipper\Zipper;
use Maatwebsite\Excel\Facades\Excel;

class excelController extends Controller
{

    public function excelParser(Request $request)
    {
        $zipper = new Zipper;
        $exel = $request->input('sheets');
        $type = $request->input('type');
        //dd($exel);
        if($type === "php") {
            $previousPrefix = '';
            $subkeys = false;
            $deep = 0;
            foreach($exel as $key1 => $val1 ) {
                foreach ($val1 as $key2 => $val2){
                        $tempString = '<?php' . PHP_EOL . 'return [' . PHP_EOL;

                        foreach ($val2 as $key3 => $val3) {
                            if(strstr($key3,'->>')){
                                $currentPrefix = '';
                                $keys = explode('->>',$key3);
                                $lastKey = array_pop($keys);
                                foreach($keys as $kkk => $kval){
                                    $currentPrefix = $currentPrefix. $kval . '|';
                                }

                                if($currentPrefix == $previousPrefix){
                                    for($i=0;$i<=count($keys);$i++) {
                                        $tempString = $tempString . '    ';
                                    }
                                    $tempString = $tempString . '"' . $lastKey . '" => "' . $val3 . '",' . PHP_EOL;
                                }else{
                                    if($subkeys){
                                        $sameKeys = 0;
                                        $checkValues = 0;
                                        $previousSubkeys = explode('|',$previousPrefix);
                                        $currentSubkeys = explode('|',$currentPrefix);
                                        array_pop( $previousSubkeys);
                                        array_pop( $currentSubkeys);

                                        if(count($previousSubkeys) > count($currentSubkeys)){
                                            $checkValues = count($currentSubkeys);
                                        }else{
                                            $checkValues = count($previousSubkeys);
                                        }

                                        for($i=0;$i<$checkValues;$i++){
                                            if($currentSubkeys[$i] == $previousSubkeys[$i] ){
                                                $sameKeys++;
                                            }else{
                                                break(1);
                                            }
                                        }

                                    if($sameKeys == 0){
                                        for($i=0;$i<$deep;$i++){
                                            for($j=0;$j<$deep - $i;$j++){
                                                $tempString = $tempString . '    ';
                                            }
                                            $tempString = $tempString . '],' . PHP_EOL;
                                        }
                                        }else{
                                            for($i=0;$i<$deep - $checkValues;$i++) {
                                                for ($j = 0; $j < $deep - $i; $j++) {
                                                    $tempString = $tempString . '    ';
                                                }
                                                $tempString = $tempString . '],' . PHP_EOL;
                                            }
                                            for ($i = 0; $i <= count($keys); $i++) {
                                                $tempString = $tempString . '    ';
                                            }
                                            $tempString = $tempString . '"' . $lastKey . '" => "' . $val3 . '",' . PHP_EOL;
                                        }
                                        $sameKeys = 0;
                                        $checkValues = 0;
                                    }else {
                                        for ($i = 0; $i < count($keys); $i++) {
                                            for ($j = 0; $j <= $i; $j++) {
                                                $tempString = $tempString . '    ';
                                            }
                                            $tempString = $tempString . '"' . $keys[$i] . '" => [' . PHP_EOL;
                                        }
                                        for ($i = 0; $i <= count($keys); $i++) {
                                            $tempString = $tempString . '    ';
                                        }
                                        $tempString = $tempString . '"' . $lastKey . '" => "' . $val3 . '",' . PHP_EOL;
                                    }
                                }
                                $subkeys = true;
                                $previousPrefix = $currentPrefix;
                                $deep = count($keys);
                            }else{
                                if($subkeys){
                                    for($i=0;$i<$deep;$i++){
                                        for($j=0;$j<$deep - $i;$j++){
                                            $tempString = $tempString . '    ';
                                        }
                                        $tempString = $tempString . '],' . PHP_EOL;
                                    }
                                    $subkeys = false;
                                }
                                $tempString = $tempString . '    "' . $key3 . '" => "' . $val3 . '",' . PHP_EOL;
                            }
                        }
                        $tempString = $tempString . '];';
                        Storage::disk('local')->put($key1 . '.php', $tempString);
                        $zipper->make(storage_path('app/translates.zip'))->folder($key2)->add(storage_path('app/' . $key1 . '.php'));
                }
            }
        }else{
            foreach($exel as $key1 => $val1 ) {
                foreach ($val1 as $key2 => $val2){
                    $tempString = 'export default {' .PHP_EOL . '    translation: {' . PHP_EOL;
                    foreach ($val2 as $key3 => $val3){
                        $tempString = $tempString . '        ' . $key3 . ': \'' . $val3 . '\',' . PHP_EOL;
                    }
                    $tempString = $tempString . '    },' . PHP_EOL . '};';
                    Storage::disk('local')->put( $key1.'.js', $tempString);
                    $zipper->make(storage_path('app/translates.zip'))->folder($key2)->add(storage_path('app/' . $key1.'.js'));
                }
            }
        }

        $zipper->close();

//        foreach($exel as $key1 => $val1 ) {
//            foreach ($val1 as $key2 => $val2) {
//                Storage::disk('local')->delete($key1. '_' . $key2.'.js');
//            }
//        }

        return response()->json([
            "path_to_file" => storage_path('app/translates.zip'),
        ]);
    }

    public function downloadTranslates(Request $request)
    {
        return response()->download($request->input("path_to_file"))->deleteFileAfterSend(true);
    }

    public function translateParser(Request $request){

        $files = $request->input('files');
        Excel::create("Translations", function($excel) use($files) {

            $excel->setTitle('Translate File');

            foreach ($files as $key0 => $val0) {

                $language = $val0["language"];
                $filename = $val0["filename"];
                $lang = $val0["lang"];
                $excel->sheet($filename, function ($sheet) use ($lang,$language){

                    $sheet->row(1, array(
                        'Key', $language
                    ));
                    $rowCounter = 2;

                    foreach ($lang as $key => $val) {
                        $sheet->row($rowCounter, array(
                            $key, $val
                        ));
                        $rowCounter++;
                    }


                });
            }

        })->store('xlsx',storage_path('excel/exports'));

        return response()->json([
            "path_to_file" => storage_path('excel/exports/Translations.xlsx'),
        ]);

    }

    public function downloadExcel(Request $request){
        return response()->download($request->input("path_to_file"))->deleteFileAfterSend(true);
    }

}
