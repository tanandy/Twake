<?php

namespace BuiltInConnectors\Common\Services;

use Emojione\Client;
use Emojione\Ruleset;

use Exception;

class MainConnectorService {

    protected $app_simple_name = "";
    protected $credentials = [];

    public function __construct($app)
    {
        $this->market_service = $app->getServices()->get("app.applications");
        $this->rest_client = $app->getServices()->get("app.restclient");
        $this->api_url = rtrim($app->getContainer()->getParameter("SERVER_NAME"), "/") . "/api/v1/";
        $this->server_url = rtrim($app->getContainer()->getParameter("SERVER_NAME"), "/") . "/bundle/connectors/";
        $this->emojione_client = new Client(new Ruleset());
    }

    public function setConnector($simple_name){
      $this->app_simple_name = $simple_name;
    }

    private function getConnectorKeys(){
      $simple_name = "twake." . $this->app_simple_name;
      if(!isset($this->credentials[$simple_name])){
        $this->credentials[$simple_name] = $this->market_service->getCredentials($simple_name);
      }
      return $this->credentials[$simple_name];
    }

    public function postApi($route, $data, $timeout = 3, $raw = false){

        $cred = $this->getConnectorKeys();

        $custom = array(
            CURLOPT_HTTPHEADER => Array(
                "Authorization: Basic ".base64_encode($cred["api_id"].":".$cred["api_key"]),
                "Content-Type: application/json"
            ),
        );

        return $this->post(rtrim($this->api_url, "/") . "/" . ltrim($route, "/"), $data, $raw, $custom, $timeout);
    }


    public function post($route, $data, $raw = false, $custom_options = Array(), $timeout = 1){
        $data_string = json_encode($data);
        $restClient = $this->rest_client;

        $custom = array(
            CURLOPT_CONNECTTIMEOUT => $timeout
        );

        foreach ($custom_options as $key=>$value){
            $custom[$key] = $value;
        }

        $resp = $restClient->post($route, $data_string, $custom);


        try {
            if(!$raw) {
                $data = json_decode($resp->getContent(), 1);
            }else{
                $data = $resp->getContent();
            }
            return $data;
        } catch (Exception $e) {
            return false;
        }
    }


    public function get($route, $raw = false, $custom_options = Array(), $timeout = 1){
        $restClient = $this->rest_client;

        $custom = array(
            CURLOPT_CONNECTTIMEOUT => $timeout
        );

        foreach ($custom_options as $key=>$value){
            $custom[$key] = $value;
        }

        $resp = $restClient->get($route, $custom);


        try {
            if(!$raw) {
                $data = json_decode($resp->getContent(), 1);
            }else{
                $data = $resp->getContent();
            }
            return $data;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getServerBaseUrl(){
        return $this->server_url;
    }

    public function generateToken($length = 10){
        try {
            return bin2hex(random_bytes(intval($length / 2)));
        }catch(\Exception $e){
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString;
        }
    }


    /**
     * @return string
     */
    public function getAppName()
    {
        return $this->app_simple_name;
    }
}