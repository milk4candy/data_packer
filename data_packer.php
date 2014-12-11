#! /usr/bin/php
<?php 

class data_packer{

    private $lib_dir = null;

    private $args = null;

    private $args_str_for_zipper = null;

    private $output_archive_filename = null;

    private $email_addr = null;

    private $mail = null;

    public function __construct($cmd_args){
        $this->lib_dir = __DIR__."/lib";
        array_shift($cmd_args);
        $this->args = $cmd_args;
        $this->archive_filename = $this->get_archive_filename();
        $this->email_addr = $this->get_email_addr();
        $this->zipper_args_str = $this->get_zipper_args_str();
        $this->init_mail();
    }

    public function get_archive_filename(){
        if(in_array('-o', $this->args)){
            $archive_filepath = $this->args[array_search('-o', $this->args) + 1];
            return basename($archive_filepath);
        }else{
            echo "Must provide archive filename using -o argument.\n";
            exit(1);
        }
    }

    public function get_email_addr(){
        if(in_array('-m', $this->args)){
            $addr = $this->args[array_search('-m', $this->args) + 1];
            return $addr;
        }else{
            echo "Must provide email using -m argument.\n";
            exit(1);
        }
    }

    public function get_zipper_args_str(){
        $args = $this->args;
        unset($args[array_search('-m', $args) + 1]);
        unset($args[array_search('-m', $args)]);
        $args_str = implode(' ', $args);
        return $args_str;
    }

    public function init_mail(){
        require("$this->lib_dir/PHPMailer/class.phpmailer.php");
        require("$this->lib_dir/PHPMailer/class.smtp.php");
        $this->mail = new PHPMailer();
        $this->mail->IsSMTP();
        $this->mail->IsHTML(true);
        $this->mail->Host = "mfcmail2.cwb.gov.tw";
        $this->mail->Port = 25;
        $this->mail->CharSet = "utf8";
        $this->mail->From = "webservice@mfcmail.cwb.gov.tw";
        $this->mail->FromName = "MFC資料服務中心";
    }

    public function pack_data(){
        exec("$this->lib_dir/zipper/zipper.php $this->zipper_args_str", $output, $exec_code);
        return $exec_code;
    }

    public function send_notification($packing_result){
    
        switch($packing_result){
            case 0:
                $subject = "您在預報中心網頁打包的資料已可供下載";
                $msg = "<html><body>如主旨，請點選下列連結下載資料<br/>".
                       "<a href=\"http://mfc.cwb.gov.tw/data/web_service/file_download/$this->archive_filename\">$this->archive_filename</a><br />".
                       "此檔將於24小時後刪除，請在此時限內完成下載</body></html>";
                break;
            case 10:
                $subject = "您在預報中心網頁打包的資料已可供下載";
                $msg = "<html><body>如主旨，請點選下列連結下載資料<br/>".
                       "<a href=\"http://mfc.cwb.gov.tw/data/web_service/file_download/$this->archive_filename\">$this->archive_filename</a><br />".
                       "您欲打包之總檔案大小超過系統上限，故本次僅打包部份檔案，請依需要適當分次打包其他部份<br />".
                       "此檔將於24小時後刪除，請在此時限內完成下載</body></html>";
                break;
            case 1:
                $subject = "您在預報中心網頁資料打包工作因故未能完成";
                $msg = "如主旨，請稍候再試一次或與系統管理者聯繫。";
                break;
            default:
        }

        $this->mail->Subject = $subject;
        $this->mail->Body = $msg;
        $this->mail->AddAddress($this->email_addr);

        if(!$this->mail->Send()){
            echo "Can't send mail:".$this->mail->ErrorInfo."\n";
            exit(1);
        }else{
            echo "Notification mail is send.\n";
            exit();
        }

    }

    public function execute(){
        $packing_result = $this->pack_data();
        $this->send_notification($packing_result);
        exit();
    }
}


$dp = new data_packer($argv);

$dp->execute();

