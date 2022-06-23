<?php
    $domain=$argv[1]; //ドメイン名入力
    $label=$argv[2]; //ラベル名入力
    $contact = '087147d64d51bc773562d3fca2104602'; //連絡先リストIDを入力する
    $api_key = '917c0ec62e30d8cf9b29e544a1559cde'; // Replace with your API Key
    echo $domain."\n";
    /* dns_get_record($domain, DNS_A)の結果が空またはnullの場合はラベルにDELETEを追加する*/
    if(!(is_array(dns_get_record($domain, DNS_A))) || empty(dns_get_record($domain, DNS_A))){
        echo "ドメイン名で名前解決できなかったので、ラベルにDELETEを追加します\n";
        $url = 'https://api.hetrixtools.com/v2/'.$api_key.'/blacklist/edit/';
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, ["target" => $domain, "label" => $label.' '.'DELETE', "contact" => $contact]);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($curl);
        curl_close($curl);
        exit();
    }
    $targets = dns_get_record($domain, DNS_A); //IPアドレスを取り出す
    $label = $domain.' '.'CLIP'; //オプションで、このモニターのラベルを追加します
    $url = 'https://api.hetrixtools.com/v2/'.$api_key.'/blacklist/add/';
    foreach($targets as $key=>$value){
        echo "ラベル".$targets[$key]['host']."\n";
        echo $targets[$key]["ip"]."を登録します\n";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, ["target" =>  $targets[$key]["ip"], "label" => $targets[$key]['host'].' '.'CLIP', "contact" => $contact]);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($curl);
        //結果を表示する
        echo $result."\n";
        curl_close($curl);

        /*このIPで登録済みの場合*/
        if($result === '{"status":"ERROR","error_message":"you are already monitoring this ip address"}'){
            $moniter_get="https://api.hetrixtools.com/v2/".$api_key."/blacklist/report/".$targets[$key]["ip"]."/";
            $curl = curl_init($moniter_get);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($curl);
            $moniter_result = json_decode($result,true);
            curl_close($curl);
            /*追加済みのIPのラベルが追加予定のラベル名と違ったら追加予定のラベルを先頭に追加する*/
            if($targets[$key]['host'].' '.'CLIP' !== $moniter_result['Label']){
                echo "このIPで紐づいてるドメインが複数見つかったので、ラベルに追加します\n";
                $url = 'https://api.hetrixtools.com/v2/'.$api_key.'/blacklist/edit/';
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, ["target" => $targets[$key]["ip"], "label" => $targets[$key]['host'].' '.$moniter_result['Label'], "contact" => $contact]);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                $result = curl_exec($curl);
                curl_close($curl);
                exit();
            }
        }
    }

?>