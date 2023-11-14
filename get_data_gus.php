<? 
namespace app\plugin\Gus;

class Run  extends \app\plugin\_PajaxPlugin
{
   
  private $sid = '?';

  public function goLogin()
  {
        try{
            
        $ch = curl_init();

        $zaloguj = 
            '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:ns="http://CIS/BIR/PUBL/2014/07">
                <soap:Header xmlns:wsa="http://www.w3.org/2005/08/addressing">
                    <wsa:To>https://wyszukiwarkaregon.stat.gov.pl/wsBIR/UslugaBIRzewnPubl.svc</wsa:To>
                    <wsa:Action>http://CIS/BIR/PUBL/2014/07/IUslugaBIRzewnPubl/Zaloguj</wsa:Action>
                </soap:Header>
                <soap:Body>
                <ns:Zaloguj>
                    <ns:pKluczUzytkownika>xxxxxxxxxxxxxxxx</ns:pKluczUzytkownika>
                </ns:Zaloguj>
                </soap:Body>
            </soap:Envelope>';
        
        curl_setopt($ch, CURLOPT_URL,"https://wyszukiwarkaregon.stat.gov.pl/wsBIR/UslugaBIRzewnPubl.svc");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $zaloguj); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT,        10); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);         
        curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: application/soap+xml;charset=UTF-8;action="http://CIS/BIR/PUBL/2014/07/IUslugaBIRzewnPubl/Zaloguj"'));         

        $server_output = curl_exec ($ch);

        curl_close ($ch);
        
        
        
      
        $start = strpos($server_output, "<ZalogujResult>");
        $stop = strpos($server_output, "</ZalogujResult>");
        
        if(is_numeric($start) && is_numeric($stop))
        {
             $start += strlen('<ZalogujResult>');
             
             $sid = substr($server_output, $start, $stop-$start);
             
             $this->sid = $sid;
             
             return $this->sid;
                        
        }else
        return NULL;
        
        
        }catch(\app\library\_PajaxException $e)  {throw $e; } 
    }


public function getDataGUSAction (\app\library\_PajaxRequest $request)
 {
   try
    {          
       
            $nazwa = '';
            $imie = '';
            $nazwisko = '';
            $ulica = '';
            $nr_domu = '';
            $telefon = '';
            $miasto = '';
            $kod_pocztowy = ''; 
            $regon = '';
            $nip = '';
            $error = '';

            if($request->isSetParam("nip")) 
            {
                $nip  = trim($request->getString("nip"));
                $nip  = str_replace("-","",$nip);
          
                if(strlen($nip)>5) 
                {                                                                              
                    $t = new \app\plugin\Gus\Run($this);
                    $ssid = $t->goLogin();
                    if( $ssid != NULL )
                    {           
                        
                      $data  = $t->checkNIP($nip); //pobranie regonu po nipie
                        
                      if(!isset($data['ErrorMessagePl']))
                      {                     
                                               
                        $dane  = $t->checkREGON( $data['Regon'], $data['SilosID']); // pobranie danych

                        if(strlen($dane['Regon'])==0)
                        {
                           $dane  = $t->checkNIP($nip); //jeżeli pusto pobranie podstawowych danych 

                           $email        = '';
                           $krs          = '';
                           $telefon      = '';                         
                        }else{
                           $email        = $dane['Email'];
                           $krs_ex       = explode("/",$dane['KRS']);
                           $krs          = $krs_ex[0];
                           $telefon      = $dane['Telefon'];   
                        }

                       $nazwa = $dane['Nazwa'];
                       $ulica = $dane['Ulica'];
                     
                       $nazwa = str_replace("&amp;", "&", $nazwa);
                       $nazwa = str_replace("&amp",  "&", $nazwa);
                       $nazwa = str_replace("&AMP",  "&", $nazwa);
                       $nazwa = str_replace("&AMP;", "&", $nazwa);
                    
                       if(empty($dane['NrNieruchomosci']))
                       {
                           $nr_lokalu        = '';
                           $nr_nieruchomosci = '';
                       }else{
                           $nr_lokalu        = trim($dane['NrLokalu']);
                           if(empty($nr_lokalu))$nr_nieruchomosci = trim($dane['NrNieruchomosci']); else $nr_nieruchomosci = trim($dane['NrNieruchomosci']).'/';
                       }
      
                       $nr_domu      = $nr_nieruchomosci.$nr_lokalu;
                   
                        if(is_numeric($dane['KodPocztowy']))
                        {
                            if(strpos($dane['KodPocztowy'],'-') == false)
                            {
                                 $kod1         = mb_substr($dane['KodPocztowy'],0,2);
                                 $kod2         = mb_substr($dane['KodPocztowy'],2,3);
                                 $kod_pocztowy = $kod1.'-'.$kod2;                           
                            }else{
                                 $kod1         = mb_substr($dane['KodPocztowy'],0,2);
                                 $kod2         = mb_substr($dane['KodPocztowy'],2,3);
                                 $kod_pocztowy = $kod1.$kod2;                           
                            }
                        }else{
                                 $kod1         = mb_substr($dane['KodPocztowy'],0,2);
                                 $kod2         = mb_substr($dane['KodPocztowy'],2,3);
                                 $kod_pocztowy = $kod1.$kod2;                         
                        }
                       
                       $miasto       = $dane['Miejscowosc'];
                       $regon        = $dane['Regon'];                      
                       
                       $dane = $t->checkDanePodmiotu($regon); //pobranie imienia i nazwiska

                       if(!empty($dane))
                       {
                           $imie = $dane['Imie'];
                           $nazwisko = $dane['Nazwisko'];
                       } 
                       
                      }else{
                          $error = $data['ErrorMessagePl'];                      
                      }
                    }
                }

            }
          
            $gus_data = array();
            
            $gus_data['nazwa']    = $nazwa;
            $gus_data['imie']     = $imie;
            $gus_data['nazwisko'] = $nazwisko;
            $gus_data['ulica']    = $ulica;
            $gus_data['nr_domu']  = $nr_domu;
            $gus_data['telefon']  = $telefon;
            $gus_data['miasto']   = $miasto;
            $gus_data['kod']      = $kod_pocztowy;
            $gus_data['regon']    = $regon;
            $gus_data['nip']      = $nip;
            $gus_data['error']    = $error; 
            
         $xxx = $this->responseAJAX(json_encode($gus_data));
         return $xxx;
                                    
       
    }catch(\app\library\_PajaxException $e)  {throw $e; } 
 }


 public function checkNIP($nip)
    {
      try{
          

          
        $ch = curl_init();

        $header = '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:ns="http://CIS/BIR/PUBL/2014/07" xmlns:dat="http://CIS/BIR/PUBL/2014/07/DataContract">    
                    <soap:Header xmlns:wsa="http://www.w3.org/2005/08/addressing"> 
                      <wsa:To>https://wyszukiwarkaregon.stat.gov.pl/wsBIR/UslugaBIRzewnPubl.svc</wsa:To>
                      <wsa:Action>http://CIS/BIR/PUBL/2014/07/IUslugaBIRzewnPubl/DaneSzukajPodmioty</wsa:Action> 
                    </soap:Header>    
                    <soap:Body>       
                        <ns:DaneSzukajPodmioty>          
                        <ns:pParametryWyszukiwania>                     
                            <dat:Nip>'.trim($nip).'</dat:Nip>                                
                            </ns:pParametryWyszukiwania>       
                        </ns:DaneSzukajPodmioty>   
                    </soap:Body> 
                    </soap:Envelope> ';
        
        curl_setopt($ch, CURLOPT_URL,"https://wyszukiwarkaregon.stat.gov.pl/wsBIR/UslugaBIRzewnPubl.svc");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $header); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT,        10); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);         
        curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: application/soap+xml;charset=UTF-8;action="http://CIS/BIR/PUBL/2014/07/IUslugaBIRzewnPubl/DaneSzukajPodmioty"','sid: '.$this->sid));                  

        $server_output = curl_exec ($ch);           
        
        curl_close ($ch);                        

        $server_output = html_entity_decode($server_output);                 
                
        $start = strpos($server_output, "<dane>"); 
          if(!is_numeric($start)) $start = strpos($server_output, "<dane>");
        $stop = strpos($server_output, "</dane>");
          if(!is_numeric($stop))    $stop = strpos($server_output, "</dane>");
           
          
       if( is_numeric($start) && is_numeric($stop) )
       {                      
            $rowsW = explode("\n", $server_output) ;
            $assoc = array();

            foreach ($rowsW as $xt => $rRow)
            {
                $exT = explode("</", $rRow);
                if(count($exT) == 2)
                {
                    $tr = explode('>', $exT[0]);
                    if(count($tr)==2 ) 
                    {
                    $expw = explode("<",  $tr[0] );                            
                    $assoc[ $expw[1] ] = $tr[1];
                    }
                }
            }

         return $assoc;
       }         
        
       return NULL;  

    }catch(\app\library\_PajaxException $e)  {throw $e; } 
 }   



public function checkREGON($regon,$silosID)
 {
    try{
 
        $ch = curl_init();

        $nazwaRaportu = '';
        if($silosID == '1') $nazwaRaportu = "BIR11OsFizycznaDzialalnoscCeidg";
        if($silosID == '2') $nazwaRaportu = "BIR11OsFizycznaDzialalnoscRolnicza";
        if($silosID == '3') $nazwaRaportu = "BIR11OsFizycznaDzialalnoscPozostala";
        if($silosID == '4') $nazwaRaportu = "";
        if($silosID == '6') $nazwaRaportu = "BIR11OsPrawna";
   
        $header = "<soap:Envelope xmlns:soap='http://www.w3.org/2003/05/soap-envelope' xmlns:ns='http://CIS/BIR/PUBL/2014/07'>
                         <soap:Header xmlns:wsa='http://www.w3.org/2005/08/addressing'>
                            <wsa:To>https://wyszukiwarkaregon.stat.gov.pl/wsBIR/UslugaBIRzewnPubl.svc</wsa:To>
                            <wsa:Action>http://CIS/BIR/PUBL/2014/07/IUslugaBIRzewnPubl/DanePobierzPelnyRaport</wsa:Action>
                            </soap:Header>
                             <soap:Body>
                              <ns:DanePobierzPelnyRaport>
                                 <ns:pRegon>".trim($regon)."</ns:pRegon>
                                 <ns:pNazwaRaportu>$nazwaRaportu</ns:pNazwaRaportu>  
                              </ns:DanePobierzPelnyRaport>
                           </soap:Body>
                         </soap:Envelope>";
  
        curl_setopt($ch, CURLOPT_URL,"https://wyszukiwarkaregon.stat.gov.pl/wsBIR/UslugaBIRzewnPubl.svc");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,  $header); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT,        10); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);         
        curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: application/soap+xml;charset=UTF-8;action="http://CIS/BIR/PUBL/2014/07/IUslugaBIRzewnPubl/DanePobierzPelnyRaport"','sid: '.$this->sid));         

        $server_output = curl_exec ($ch);  

        curl_close ($ch);
        
        $server_output = html_entity_decode($server_output);                 
        
        
        $start = strpos($server_output, "<dane>"); 
          if(!is_numeric($start)) $start = strpos($server_output, "<dane>");
        $stop = strpos($server_output, "</dane>");
          if(!is_numeric($stop))  $stop = strpos($server_output, "</dane>");
           
          
       if( is_numeric($start) && is_numeric($stop) )
       {                      
            $rowsW = explode("\n", $server_output) ;
            $assoc = array();

            foreach ($rowsW as $xt => $rRow)
            {
                $exT = explode("</", $rRow);
                if(count($exT) == 2)
                {
                    $tr = explode('>', $exT[0]);
                    if(count($tr)==2 ) 
                    {
                    $expw = explode("<",  $tr[0] );                            
                    $assoc[ $expw[1] ] = $tr[1];
                    }
                }
            }
            
           $dane = array();
            
           if($silosID == 1 || $silosID == 2 || $silosID == 3) //CEIDG , Rolnicza, Inna np.komornik
           {
             $dane['Regon']           = isset($assoc['fiz_regon9']) ? $assoc['fiz_regon9'] : '';
             $dane['Nazwa']           = isset($assoc['fiz_nazwa']) ? $assoc['fiz_nazwa'] : '';
             $dane['Wojewodztwo']     = isset($assoc['fiz_adSiedzWojewodztwo_Nazwa']) ? $assoc['fiz_adSiedzWojewodztwo_Nazwa'] : '';
             $dane['Powiat']          = isset($assoc['fiz_adSiedzPowiat_Nazwa']) ? $assoc['fiz_adSiedzPowiat_Nazwa'] : '';
             $dane['Gmina']           = isset($assoc['fiz_adSiedzGmina_Nazwa']) ? $assoc['fiz_adSiedzGmina_Nazwa'] : '';
             $dane['Miejscowosc']     = isset($assoc['fiz_adSiedzMiejscowosc_Nazwa']) ? $assoc['fiz_adSiedzMiejscowosc_Nazwa'] : '';
             $dane['KodPocztowy']     = isset($assoc['fiz_adSiedzKodPocztowy']) ? $assoc['fiz_adSiedzKodPocztowy'] : '';
             $dane['Ulica']           = isset($assoc['fiz_adSiedzUlica_Nazwa']) ? $assoc['fiz_adSiedzUlica_Nazwa'] : '';
             $dane['NrNieruchomosci'] = isset($assoc['fiz_adSiedzNumerNieruchomosci']) ? $assoc['fiz_adSiedzNumerNieruchomosci'] : '';
             $dane['NrLokalu']        = isset($assoc['fiz_adSiedzNumerLokalu']) ? $assoc['fiz_adSiedzNumerLokalu'] : '';
             $dane['KRS']             = '';
             $dane['Email']           = isset($assoc['fiz_adresEmail']) ? $assoc['fiz_adresEmail'] : '';
             $dane['Telefon']         = isset($assoc['fiz_numerTelefonu']) ? $assoc['fiz_numerTelefonu'] : '';
           }                          
           
           if($silosID == 6) //Os.Prawna
           {
             $dane['Regon']           = isset($assoc['praw_regon9']) ? $assoc['praw_regon9'] : '';
             $dane['Nazwa']           = isset($assoc['praw_nazwa']) ? $assoc['praw_nazwa'] : '';
             $dane['Wojewodztwo']     = isset($assoc['praw_adSiedzWojewodztwo_Nazwa']) ? $assoc['praw_adSiedzWojewodztwo_Nazwa'] : '';
             $dane['Powiat']          = isset($assoc['praw_adSiedzPowiat_Nazwa']) ? $assoc['praw_adSiedzPowiat_Nazwa'] : '';
             $dane['Gmina']           = isset($assoc['praw_adSiedzGmina_Nazwa']) ? $assoc['praw_adSiedzGmina_Nazwa'] : '';
             $dane['Miejscowosc']     = isset($assoc['praw_adSiedzMiejscowosc_Nazwa']) ? $assoc['praw_adSiedzMiejscowosc_Nazwa'] : '';
             $dane['KodPocztowy']     = isset($assoc['praw_adSiedzKodPocztowy']) ? $assoc['praw_adSiedzKodPocztowy'] : '';
             $dane['Ulica']           = isset($assoc['praw_adSiedzUlica_Nazwa']) ? $assoc['praw_adSiedzUlica_Nazwa'] : '';
             $dane['NrNieruchomosci'] = isset($assoc['praw_adSiedzNumerNieruchomosci']) ? $assoc['praw_adSiedzNumerNieruchomosci'] : '';
             $dane['NrLokalu']        = isset($assoc['praw_adSiedzNumerLokalu']) ? $assoc['praw_adSiedzNumerLokalu'] : '';
             $dane['KRS']             = '';
             $dane['Email']           = isset($assoc['praw_adresEmail']) ? $assoc['praw_adresEmail'] : '';
             $dane['Telefon']         = isset($assoc['praw_numerTelefonu']) ? $assoc['praw_numerTelefonu'] : '';
           }

             return $dane;
         
            
       }         
        
       return NULL;

    }catch(\app\library\_PajaxException $e)  {throw $e; } 
 }



  
}
?>
