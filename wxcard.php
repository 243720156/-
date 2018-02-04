<?php 
namespace wxcard;

/**
* 微信会员卡类
*   如有不明白可联系qq:243720156
* 
*	使用方法：
*	//将wxcard放到extend/wxcard文件夹下
*	//先在控制器头部use wxcard\wxcard;
*	$wxcard=new wxcard(appid,secret);
* 	$appid='wxc**************';
*   $secret='3e****************';
*   //实例化会员卡类
*   $wxcard=new wxcard($appid,$secret);
*   //设置会员卡字段 详见会员文档https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1451025283
*   $file = file_get_contents( "json文件路径" ),
*   //或者直接控制器中手动创建json数组，
*   //记得json_decode($array,JSON_UNESCAPED_UNICODE);为保护中文字符不被编码,否则微信会返回47001格式错误
*   //以上为会员填写信息的一键激活会员卡创建,自动激活
* 
*   //1.卡券创建
*   $data=$wxcard->wxCardCreated($file);
*   $card_id=$data['card_id'];
*   //2.设置一键激活需要填写的字段值
*   $act_data = file_get_contents( "https://wx.yjdlm.com/static/wxjson/wxcardact.json" );
*   $active=$wxcard->wxAutoActive($act_data,$card_id);
*   //3.获取二维码ticket
*   $ticket_info=$wxcard->wxQrCodeTicket($card_id);
*   $ticket=$ticket_info['ticket'];
*   //4.利用ticket获取二维码链接
*   $card_qrcode=$wxcard->wxQrCode($ticket);
*   echo "会员卡二维码链接:".$card_qrcode; 
*
* 
**/
class wxcard
{

	private $appid = null;
    private $secret = null;

    public function __construct($_appid, $_secret)
    {
        $this->appid = $_appid;
        $this->secret = $_secret;
    }

    //1.获取access_token
    Public function wxAccessToken(){
        $appid=$this->appid;
        $secret=$this->secret;
        $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret;
        $data=file_get_contents($url);
        $access_token=json_decode($data,true);
        $access_token=$access_token['access_token'];

        return $access_token;
    }


    /*******************************************************
    * 微信卡券：创建卡券
    * 传入参数JSON：
    * jsonData=>{
    * 	见附件 wxmember.json
    * }
    * 创建成功返回信息：
    * return=>array{
    * 		["errcode"] => int(0)
  	*		["errmsg"] => string(2) "ok"
  	*		["card_id"] => string(28) "p_ylK0d3OY_VvEmJ3rOJ2VcC9q0w"
    * }
    * 
    *******************************************************/
    public function wxCardCreated($jsonData) {
        $wxAccessToken = $this->wxAccessToken();
        //处理jsonData,将json数据中的前置内容处理干净
        while( $jsonData[0] != '{' ){
            $jsonData = substr( $jsonData, 1 );
        }
        $url = "https://api.weixin.qq.com/card/create?access_token=" . $wxAccessToken;
        $result = $this->wxHttpsRequest($url,$jsonData);
        $jsoninfo = json_decode($result, true);
        return $jsoninfo;
    }


    /****************************************************
     *    微信提交API方法，返回微信指定JSON
     ****************************************************/

    public function wxHttpsRequest($url,$data = null){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }




    /****************************************************
     *    设置微信激活需要填写的字段
     *    https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1451025283 见设置开卡字段接口
     *    
     *    $jsonData=>json文件，需json_encode($array,JSON_UNESCAPED_UNICODE);保留中文字符
     *   
     ****************************************************/
    public function wxAutoActive($jsonData,$card_id)
    {
    	$access_token=$this->wxAccessToken();
    	//设置一键激活需要填写的字段值
        $act_url="https://api.weixin.qq.com/card/membercard/activateuserform/set?access_token=".$access_token;
        $jsonData=json_decode($jsonData,true);
        $jsonData['card_id']=$card_id;
        $jsonData=json_encode($jsonData,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        while( $jsonData[0] != '{' ){
            $jsonData = substr( $jsonData, 1 );
        }
        $active=$this->wxHttpsRequest($act_url,$jsonData);
        // dump($active);
        return $active;
    	
    }


    /****************************************************
     *    微信生成二维码ticket
     *    $card_id=> 生成的会员卡ID
     *
     *		此方法也可将card_id改为json_Data然后在控制器中创建jsonData传入
     *		//获取二维码ticket
     *  	$ticket_data=[
     *  	    "action_name"=> "QR_CARD", 
     *  	    "action_info"=> [
     *  	    "card"=> [
     *  	    "card_id"=> $card_id, 
     *  	            ]
     *  	    ]
     *  	];
     *  	//获取二维码ticket
     *  	$ticket_data=json_encode($ticket_data); 
     * 
     ****************************************************/

    public function wxQrCodeTicket($card_id){
        $wxAccessToken     = $this->wxAccessToken();
        //获取二维码ticket
        $ticket_data=[
            "action_name"=> "QR_CARD", 
            "action_info"=> [
            "card"=> [
            "card_id"=> $card_id, 
                    ]
            ]
        ];
        //获取二维码ticket
        $jsonData		=json_encode($ticket_data);
        $url        	= "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$wxAccessToken;
        $ticket_return  = $this->wxHttpsRequest($url,$jsonData);
        $result=json_decode($ticket_return,true);
        return $result;
    }


    /****************************************************
     *    微信生成二维码图片链接
     *    输入 上一步生成的ticket
     *    
     ****************************************************/

    public function wxQrCode($ticket){
        $url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=" . urlencode($ticket);
        return $url;//二维码图片链接
    }


    /***************************
    *	开卡后会员卡激活
    * 	需传入会员卡code和会员卡card_id
    *
    * 	传入数据
    *  $data=array(
    *  	'code' => "卡券解密后的真实code",
    *  	'card_id'=>"卡券的card_id",
    *  	'field_value'=>"等级信息"
    *  );
    *
    *  成功返回数据json
    *  {errcode:0,errmsg:ok,}
    *  
    */
    Public function wxCardActive($data){
        $wxAccessToken = $this->wxAccessToken();
        $active_data=[
            "init_bonus"=> 100,
            "init_bonus_record"=>"旧积分同步",
            "init_balance"=> 200,
            "membership_number"=> $data['code'],
            "code"=> $data['code'],
            "card_id"=> $data['card_id'],
            "background_pic_url"=> "https://mmbiz.qlogo.cn/mmbiz/0?wx_fmt=jpeg",
            "init_custom_field_value1"=> $data['field_value']
        ];

        $jsonData=json_encode($active_data,JSON_UNESCAPED_UNICODE);
        $url = "https://api.weixin.qq.com/card/membercard/activate?access_token=".$wxAccessToken;
        $result = $this->wxHttpsRequest($url,$jsonData);
        $jsoninfo = json_decode($result, true);
        if ($jsoninfo['errcode']!==0) {
        	return $jsoninfo['errmsg'];
        	exit();
        }

        return $jsoninfo;

    }


    /***********************************
    *	卡券code解密
    *	传入参数
    *	开卡成功后的 encrypt_code;
    *
    *	return:
    *	{	errcode:0,
    *		errmsg:ok,
    *		code:卡券的真实code
    *	}
    *
    * 
     ***********************************/

    Public function encrypt_code($encrypt_code){

        $encrypt=array('encrypt_code'=>$encrypt_code);
        $encrypt_code=json_encode($encrypt);
        $url="https://api.weixin.qq.com/card/code/decrypt?access_token=".$this->wxAccessToken();
        //解密encrypt_code获取真实code
        $code_data=$this->wxHttpsRequest($url,$encrypt_code);
        $code_data=json_decode($code_data,true); 
        if ($code_data['errcode']!==0) {
            return $code_data['errmsg'];
            exit();
        }

        return $code_data;
    }










}