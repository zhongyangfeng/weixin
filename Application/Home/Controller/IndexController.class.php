<?php
namespace Home\Controller;
use Think\Controller;

class IndexController extends Controller {

    private $app_id = 'wxd299b42f2c97067f';
    private $app_secret = '6bb91bd272f175b6219ab202a0e52ef8';

    public function index(){

    	//$User = M('User');
		// 和用法 $User = new \Think\Model('User'); 等效
		// 执行其他的数据操作
		//$user = $User->select();
		//print_r($user);exit;

    	define("TOKEN", "weixin");
    	if (!isset($_GET['echostr'])) {
		    $this->responseMsg();
		}else{
		    $this->valid();
		}
    }

    //验证签名
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if($tmpStr == $signature){
            echo $echoStr;
            exit;
        }
    }

    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)){
            $this->logger("R ".$postStr);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);

            $result = "";
            switch ($RX_TYPE)
            {
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text":
                    $result = $this->receiveText($postObj);
                    break;
            }
            $this->logger("T ".$result);
            echo $result;
        }else {
            echo "";
            exit;
        }
    }

    private function receiveEvent($object)
    {
        switch ($object->Event)
        {
            case "subscribe":
            $content = $this->test($object->FromUserName);
            break;
        }
        $result = $this->transmitText($object, $content);
        return $result;
    }

    private function test($openid){
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->app_id.'&secret='.$this->app_secret;
        $data = $this->http($url);
        $_data = json_decode($data);
        $access_token = $_data->access_token;

        $url02 = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        $userdata = $this->http($url02);
        $userinfo = json_decode($userdata);

        $info['openid'] = $userinfo->openid;
        $info['nickname'] = $userinfo->nickname;
        $info['sex'] = $userinfo->sex;
        $info['language'] = $userinfo->language;
        $info['city'] = $userinfo->city;
        $info['province'] = $userinfo->province;
        $info['country'] = $userinfo->country;
        $info['headimgurl'] = $userinfo->headimgurl;
        $info['subscribe_time'] = $userinfo->subscribe_time;
        $info['unionid'] = $userinfo->unionid;
        $info['remark'] = $userinfo->remark;
        $info['groupid'] = $userinfo->groupid;

        $User = M('Wx_user');
        $User->data($info)->add();
        // 和用法 $User = new \Think\Model('User'); 等效
        // 执行其他的数据操作
        //$data = $User->select();
        //var_dump($data);exit;

    	return "获取用户信息:".$userdata;
    }



    private function receiveText($object)
    {
        $keyword = trim($object->Content);
        $url = "http://apix.sinaapp.com/weather/?appkey=".$object->ToUserName."&city=".urlencode($keyword); 
        $output = file_get_contents($url);
        $content = json_decode($output, true);

        $result = $this->transmitNews($object, $content);
        return $result;
    }

    private function transmitText($object, $content)
    {
        if (!isset($content) || empty($content)){
            return "";
        }
        $textTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[text]]></MsgType>
			<Content><![CDATA[%s]]></Content>
			</xml>";
        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }

    private function transmitNews($object, $newsArray)
    {
        if(!is_array($newsArray)){
            return "";
        }
        $itemTpl = "<item>
		        <Title><![CDATA[%s]]></Title>
		        <Description><![CDATA[%s]]></Description>
		        <PicUrl><![CDATA[%s]]></PicUrl>
		        <Url><![CDATA[%s]]></Url>
		    </item>
		";
        $item_str = "";
        foreach ($newsArray as $item){
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $newsTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[news]]></MsgType>
			<Content><![CDATA[]]></Content>
			<ArticleCount>%s</ArticleCount>
			<Articles>
			$item_str</Articles>
			</xml>";

        $result = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        return $result;
    }

    private function logger($log_content)
    {
      
    }


    /**
     * Http方法
     * 
     */ 
    private function http($url) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        //curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $res = curl_exec($curl);
        curl_close($curl);
        //var_dump($res);exit;
        return $res;
    }


}