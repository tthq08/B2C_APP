<?php
namespace Translate;


class Translate
{


    function doIn($data)
    {
        foreach ($data as $key=>$item){
            $translate = translate($item, "CN", "auto");
            if( !empty($translate['web'][0]['value']) ){
                if( isset($translate['web'][0]['value']) ){
                    $translateValue = $translate['web'][0]['value'][0];
                }else{
                    $translateValue = $translate['web'][0]['value'];
                }
            }else{
                if( empty($translate['translation']) ){
                    print_r($key);
                    print_r($translate);
                    echo '<br/>';
                    $reData[$key] = $item;
                }
                $translateData = $translate['translation'][0];
                if( isset($translate[0]) ){
                    $translateValue = $translateData[0];
                }else{
                    $translateValue = $translateData;
                }
            }

            $reData[$key] = $translateValue;
            $dataTxt .= "'{$key}' => '{$reData[$key]}',<br/>";
        }

    }
}


