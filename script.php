<?php
/*************************************************
скрипт для заполнения xlsx файлов для гис жкх
*************************************************/
echo "\nstart script ".__FILE__."\n";

include_once(dirname(__FILE__)."/src/github.com/PHPOffice/PHPExcel/Classes/PHPExcel.php");
include_once(dirname(__FILE__)."/src/github.com/mixamarciv/std/class_myDb.php");
include_once(dirname(__FILE__)."/src/github.com/mixamarciv/std/paramParser.php");
include_once(dirname(__FILE__)."/src/github.com/mixamarciv/std/myFile.php");

$start0_time = my_microtime();
//------------------------------------------------------------------------------
//получаем список основных параметроф
$p = new paramParser();  
$p->init(); 

$in_file     = $p->fvar("from");
$out_file    = $p->fvar("to");

$type        = $p->fvar("type");
$fcomp       = $p->fvar("fcomp");
$fperiod     = $p->fvar("fperiod");

$all_vars = $p->fvars();

$log_file = __file__.".log";
$db = connect_to_db("ibase","192.168.1.10:d:/_db_web/db002/0002.fdb","sysdba","masterkey","win1251",null);

if(file_exists($out_file)){
    unlink($out_file);
}

$start1_time = my_microtime();
$b = 0;
if($type=="export_flats"){
    wlog("run export_flats \n");
    export_flats($p);
    $b = 1;
}else 
if($type=="export_lc"){
    wlog("run export_lc \n");
    export_lc($p);
    $b = 1;
}else 
if($type=="import_lc_from_elc"){
    wlog("run import_lc_from_elc \n");
    import_lc_from_elc($p);
    $b = 1;
}else 
if($type=="import_lc_from_result"){
    wlog("run import_lc_from_result \n");
    import_lc_from_result($p);
    $b = 1;
}

if($b==0){
    wlog("неверно указан тип обработки type: \"{$type}\" \n");
}

wlog("render/total time: ".show_time($start1_time)." / ".show_time($start0_time)."\n");
wlog("the end\n\n");
exit();
//-----------------------------------------------------------------------------------------------------------------------------
function connect_to_db($db_driver,$db_database,$db_user_name,$db_user_password,$db_codepage,$db_role){
    $db = new myDB();
    $b = $db->connect($db_driver,$db_database,$db_user_name,$db_user_password,$db_codepage,$db_role);
    if(!$b){
        $msg = ("подключение к БД $driver ($db_driver,$db_database,$db_user_name,$db_user_password,$db_codepage,$db_role) НЕ УСТАНОВЛЕНО!\n").$db->last_error();
        wlog($msg."\n");
        exit(1);
    }
    wlog("подключения к БД $db_driver УСПЕШНО УСТАНОВЛЕНО!\n");
    return $db;
}

function ttr($text){
    return tr($text,"UTF-8","cp866");
}

function trq($text){
    return tr($text,"cp1251","UTF-8");
}

function exec_query($sql){
    global $db;
    $q = $db->query();
    $b = $q->exec($sql);
    if($b==0){
        $msg = ("ошибка выполнения запроса к БД ".$db->info().":\n").$q->last_error()."\nquery:\n".$sql;
        wlog($msg."\n");
        exit(1);
    }
    return $q;
}

function wlog($msg){
    global $log_file;
    echo ttr($msg);
    my_writeToFile($log_file,"ab",my_datetime_to_str().": ".$msg);
}

function show_time($t){
    return my_microtime_to_str(my_microtime()-$t);
}
//-----------------------------------------------------------------------------------------------------------------------------
function check_arg_error($type,$p){
    if($p!="") return; 
    $msg = "ошибка: не задан обязательный параметр {$type}\n";
    wlog($msg."\n");
    exit(1);
}

function export_flats($p){
    $in_file     = $p->fvar("from");
    $out_file    = $p->fvar("to");
    $fcomp       = $p->fvar("fcomp");
    $fperiod     = $p->fvar("fperiod");
    
    $objPHPExcel = PHPExcel_IOFactory::load($in_file);
    {//заполняем данные по квартирам:
        $objPHPExcel->setActiveSheetIndex(9);
        $objSheet = $objPHPExcel->getActiveSheet();
        $query1 = "";
        {
            $query1 = "
                SELECT
                  \"дом\",
                  \"кв\",
                  \"подъезд\",
                  \"тип квартиры\",
                  MAX(t.\"общ.площадь\") AS \"общ.площадь\",
                  MAX(t.\"жил.площадь\") AS \"жил.площадь\",
                  \"ГКН\"
                FROM
                (
                SELECT 
                  '['||nh.fcomp || '] '||cn.name AS \"УК\",
                  nh.street||' '||h.house AS \"дом\",
                  f1.flat2 AS \"кв\",
                  COALESCE(f2.entrance,0) AS \"подъезд\",
                  'Отдельная квартира' AS \"тип квартиры\",
                  k.ob_area AS \"общ.площадь\",
                  COALESCE(
                    IIF(COALESCE(k.jil_area,0)!=0,k.jil_area,(SELECT MAX(t.jil_area) FROM kv2_kart t WHERE t.fcomp_period='70-'||nh.fperiod AND t.lcode=f1.lcode)),
                    k.ob_area) 
                    AS \"жил.площадь\",
                  IIF(COALESCE(f2.kadastrn1,'')!='',f2.kadastrn1,'::'||nh.strcode||':'||nh.house2||':'||COALESCE(h.build_year,'0000')||':'||f1.flat2) AS \"ГКН\"
                
                FROM
                  t_kv2_nachisl_info_house nh
                  LEFT JOIN t_obj_house h ON h.strcode=nh.strcode AND h.house=nh.house2
                
                  LEFT JOIN t_kv2_nachisl_info f1 ON f1.fcpsh=nh.fcpsh
                  LEFT JOIN t_obj_flat f2 ON f2.strcode=nh.strcode AND f2.house=nh.house2 AND f2.flat=f1.flat2
                
                  LEFT JOIN kv2_kart k ON k.fcomp_period=nh.fcomp_period AND k.lcode=f1.lcode
                
                  LEFT JOIN company_name cn ON cn.ncomp=nh.fcomp
                
                WHERE 1=1
                  AND nh.prc_has_nachisl > 30
                  AND nh.fcomp = {$fcomp}
                  AND nh.fperiod LIKE '{$fperiod}'
                ) t
                WHERE 1=1
                GROUP BY
                  \"УК\",
                  \"дом\",
                  \"кв\",
                  \"подъезд\",
                  \"тип квартиры\",
                  \"ГКН\"
                ORDER BY \"дом\",\"кв\",\"тип квартиры\"
                ";
        }
        $q = exec_query(tr($query1,"UTF-8","cp1251"));
        $i = 0;
        while( $row = $q->fetch_row() ){
            $i++;
            $objSheet->setCellValue("A".($i+2),(string)trq($row[0]));
            $objSheet->setCellValue("B".($i+2),(string)trq($row[1]));
            $objSheet->setCellValue("C".($i+2),(string)trq($row[2]));
            $objSheet->setCellValue("D".($i+2),(string)trq($row[3]));
            $objSheet->setCellValue("E".($i+2),(string)trq($row[4]));
            $objSheet->setCellValue("F".($i+2),(string)trq($row[5]));
            $objSheet->setCellValue("G".($i+2),(string)trq($row[6]));
        }
        wlog("flats rows: $i \n");
    }
    
    {//заполняем данные по подъездам:
        $objPHPExcel->setActiveSheetIndex(6);
        $objSheet = $objPHPExcel->getActiveSheet();
        $query1 = "";
        {
            $query1 = "
                SELECT 
                  nh.street||' '||h.house AS \"дом\",
                  COALESCE(e.entrance,0) AS \"подъезд\",
                  h.floor_cnt AS \"количество этажей\",
                  '01.01.'||h.build_year AS \"дата постройки\"
                FROM
                  t_kv2_nachisl_info_house nh
                  LEFT JOIN t_obj_house h ON h.strcode=nh.strcode AND h.house=nh.house2
                  LEFT JOIN t_obj_entrance e ON e.strcode=nh.strcode AND e.house=nh.house2
                  LEFT JOIN company_name cn ON cn.ncomp=nh.fcomp
                WHERE 1=1
                  AND nh.prc_has_nachisl > 30
                  AND nh.fcomp = {$fcomp}
                  AND nh.fperiod LIKE '{$fperiod}'
                ORDER BY \"дом\",\"подъезд\"
                ";
        }
        $q = exec_query(tr($query1,"UTF-8","cp1251"));
        $i = 0;
        while( $row = $q->fetch_row() ){
            $i++;
            $objSheet->setCellValue("A".($i+2),(string)trq($row[0]));
            $objSheet->setCellValue("B".($i+2),(string)trq($row[1]));
            $objSheet->setCellValue("C".($i+2),(string)trq($row[2]));
            $objSheet->setCellValue("D".($i+2),(string)trq($row[3]));
        }
        wlog("entrance rows: $i \n");
    }
    
    {//заполняем данные по домам:
        $objPHPExcel->setActiveSheetIndex(0);
        $objSheet = $objPHPExcel->getActiveSheet();
        $query1 = "";
        {
            $query1 = "
                SELECT 
                  nh.street||' '||h.house AS \"дом\",
                  h.fiasguid AS \"код ФИАС\",
                  '87715000001' AS \"OKTMO\",
                  'Исправный' AS \"состояние\",
                  COALESCE(nh.ob_area,(SELECT MAX(t.ob_area) FROM t_kv2_nachisl_info_house t WHERE t.fcpsh='70-'||nh.fperiod||'-'||nh.fstrcode_house)) AS \"общ.площадь\",
                  h.build_year AS \"год постройки\",
                  h.floor_cnt AS \"количество этажей\",
                  0  AS \"количество подземных этажей\",
                  h.floor_cnt AS \"минимальное количество этажей\",
                  'Москва' AS \"часовая зона\",
                  'Нет' AS \"объект культурного наследия\",
                  IIF(COALESCE(h.kadastrn1,'')!='',kadastrn1,'::'||h.strcode||':'||h.house||':'||h.build_year) AS \"ГКН\"
                FROM
                  t_kv2_nachisl_info_house nh
                  LEFT JOIN t_obj_house h ON h.strcode=nh.strcode AND h.house=nh.house2
                  LEFT JOIN company_name cn ON cn.ncomp=nh.fcomp
                
                WHERE 1=1
                  AND nh.prc_has_nachisl > 30
                  AND nh.fcomp = {$fcomp}
                  AND nh.fperiod LIKE '{$fperiod}'
                ORDER BY \"дом\"
                ";
        }
        $q = exec_query(tr($query1,"UTF-8","cp1251"));
        $i = 0;
        while( $row = $q->fetch_row() ){
            $i++;
            $objSheet->setCellValue("A".($i+2),(string)trq($row[0]));
            $objSheet->setCellValue("B".($i+2),(string)trq($row[1]));
            $objSheet->setCellValue("C".($i+2),(string)trq($row[2]));
            $objSheet->setCellValue("D".($i+2),(string)trq($row[3]));
            $objSheet->setCellValue("E".($i+2),(string)trq($row[4]));
            $objSheet->setCellValue("F".($i+2),(string)trq($row[5]));
            $objSheet->setCellValue("G".($i+2),(string)trq($row[6]));
            $objSheet->setCellValue("H".($i+2),(string)trq($row[7]));
            $objSheet->setCellValue("I".($i+2),(string)trq($row[8]));
            $objSheet->setCellValue("J".($i+2),(string)trq($row[9]));
            $objSheet->setCellValue("K".($i+2),(string)trq($row[10]));
            $objSheet->setCellValue("L".($i+2),(string)trq($row[11]));
        }
        wlog("house rows: $i \n");
    }
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($out_file);
}


function export_lc($p){
    $in_file     = $p->fvar("from");
    $out_file    = $p->fvar("to");
    $fcomp       = $p->fvar("fcomp");
    $fperiod     = $p->fvar("fperiod");
    $street      = tr($p->fvar("street"),"cp1251","UTF-8");
    $house       = tr($p->fvar("house"),"cp1251","UTF-8");
    $flat        = tr($p->fvar("flat"),"cp1251","UTF-8");
    $lcode       = $p->fvar("lcode");
    
    if($street=="") $street = "%";
    if($house=="" ) $house = "%";
    if($flat==""  ) $flat = "%";
    if($lcode=="" ) $lcode = "%";
    
    
    $objPHPExcel = PHPExcel_IOFactory::load($in_file);
    
    {//заполняем данные на листе1:
        $objPHPExcel->setActiveSheetIndex(0);
        $objSheet = $objPHPExcel->getActiveSheet();
        $query1 = "";
        {
            $query1 = "
                SELECT 
                  h.fcomp||f.lcode AS n,
                  IIF(h.fcomp = 2, f.lcode, h.fcomp||f.lcode) AS lc,
                  TRIM(COALESCE((SELECT MAX(tt.gis_idjku) FROM t_gis_lc tt 
                                 WHERE tt.fulllcode=IIF(h.fcomp = 2, f.lcode, h.fcomp||f.lcode)
                                 ),' ')) AS id_jku,
                  TRIM(IIF(h.fcomp != 70, 'ЛС УО','ЛС РСО')) AS tip_lc,
                  (CASE WHEN (SELECT SUM(tkn.nachisl_naem) FROM t_kv2_nachisl_info tkn 
                              WHERE tkn.fcpsh='60-'||h.fperiod||'-'||h.fstrcode_house
                                AND tkn.flat2=f.flat2
                              ) > 0 
                        THEN 'Да'
                        ELSE 'Нет'
                   END
                  ) AS nanimatelb,
                  lcfio.fam,
                  lcfio.name,
                  lcfio.pat,
                  TRIM(' ') AS snils,
                  TRIM(' ') AS viddoc,
                  TRIM(' ') AS ndoc,
                  TRIM(' ') AS sdoc,
                  TRIM(' ') AS datedoc,
                  TRIM(' ') AS ogrn,
                  TRIM(' ') AS nza,
                  TRIM(' ') AS kpp,  -- 15
                  COALESCE((SELECT SUM(ttc.ob_area) FROM t_kv2_nachisl_info ttf 
                     LEFT JOIN t_kv2_nachisl_info_calc ttc ON ttc.fcomp_period_lcode=ttf.fcomp_period_lcode 
                   WHERE ttf.fcpsh=h.fcpsh
                     AND ttf.flat2=f.flat2
                     AND ttf.nachisl_sum > 0
                     AND ttf.nachisl_hz_nachisl < ttf.nachisl_sum
                   ),0) AS ob_area,
                  TRIM(' ') AS jil_area,
                  TRIM(' ') AS otopl_area,
                  TRIM(' ') AS kol_chel  -- 19
                FROM t_kv2_nachisl_info_house h
                  LEFT JOIN t_kv2_nachisl_info f ON f.fcpsh=h.fcpsh
                  LEFT JOIN t_kv2_nachisl_info_calc c ON c.fcomp_period_lcode=f.fcomp_period_lcode
                  LEFT JOIN t_obj_house th ON th.fstrcode_house=h.fstrcode_house 
                  LEFT JOIN t_obj_flat tf ON tf.fstrcode_house=h.fstrcode_house AND tf.flat=f.flat2
                  LEFT JOIN t_lc_fio lcfio ON lcfio.fcomp_period_lcode=f.fcomp_period_lcode
                WHERE 1=1
                  AND h.fcomp_period  LIKE '{$fcomp}-{$fperiod}'
                  AND h.prc_has_nachisl > 30
                  AND UPPER(h.street) LIKE UPPER('{$street}')
                  AND h.house2 LIKE UPPER('{$house}')
                  AND f.flat2 LIKE UPPER('{$flat}')
                  AND f.lcode LIKE '{$lcode}'
                ORDER BY h.street,h.house_order,f.flat_order,f.lcode
                ";
        }
        $q = exec_query(tr($query1,"UTF-8","cp1251"));
        $i = 0;
        while( $row = $q->fetch_row() ){
            $i++;
            $objSheet->setCellValue("A".($i+2),(string)trq($row[0]));
            $objSheet->setCellValue("B".($i+2),(string)trq($row[1]));
            $objSheet->setCellValue("C".($i+2),(string)trq($row[2]));
            $objSheet->setCellValue("D".($i+2),(string)trq($row[3]));
            $objSheet->setCellValue("E".($i+2),(string)trq($row[4]));
            $objSheet->setCellValue("F".($i+2),(string)trq($row[5]));
            $objSheet->setCellValue("G".($i+2),(string)trq($row[6]));
            $objSheet->setCellValue("H".($i+2),(string)trq($row[7]));
            $objSheet->setCellValue("I".($i+2),(string)trq($row[8]));
            $objSheet->setCellValue("J".($i+2),(string)trq($row[9]));
            $objSheet->setCellValue("K".($i+2),(string)trq($row[10]));
            $objSheet->setCellValue("L".($i+2),(string)trq($row[11]));
            $objSheet->setCellValue("M".($i+2),(string)trq($row[12]));
            $objSheet->setCellValue("N".($i+2),(string)trq($row[13]));
            $objSheet->setCellValue("O".($i+2),(string)trq($row[14]));
            $objSheet->setCellValue("P".($i+2),(string)trq($row[15]));
            $objSheet->setCellValue("Q".($i+2),(string)trq($row[16]));
            $objSheet->setCellValue("R".($i+2),(string)trq($row[17]));
            $objSheet->setCellValue("S".($i+2),(string)trq($row[18]));
            $objSheet->setCellValue("T".($i+2),(string)trq($row[19]));
        }
        wlog("list1 rows: $i \n");
    }
    
    {//заполняем данные на листе2:
        $objPHPExcel->setActiveSheetIndex(1);
        $objSheet = $objPHPExcel->getActiveSheet();
        $query1 = "";
        {
            $query1 = "
                SELECT 
                  h.fcomp||f.lcode AS n,
                  h.street||' '||h.house2||'-'||f.flat2 AS adres,
                  th.fiasguid,
                  tf.flat,
                  TRIM(' ') AS nomer_komnati,
                  TRIM(' ') AS id_in_gis,
                  IIF(f.nachisl_sum > 0 AND f.nachisl_hz_nachisl < f.nachisl_sum,
                  c.ob_area / 
                  COALESCE((SELECT SUM(ttc.ob_area) FROM t_kv2_nachisl_info ttf 
                     LEFT JOIN t_kv2_nachisl_info_calc ttc ON ttc.fcomp_period_lcode=ttf.fcomp_period_lcode 
                   WHERE ttf.fcpsh=h.fcpsh
                     AND ttf.flat2=f.flat2
                     AND ttf.nachisl_sum > 0
                     --AND ttf.nachisl != 0
                   ),1)*100,0) AS prc
                FROM t_kv2_nachisl_info_house h
                  LEFT JOIN t_kv2_nachisl_info f ON f.fcpsh=h.fcpsh
                  LEFT JOIN t_kv2_nachisl_info_calc c ON c.fcomp_period_lcode=f.fcomp_period_lcode
                  LEFT JOIN t_obj_house th ON th.fstrcode_house=h.fstrcode_house 
                  LEFT JOIN t_obj_flat tf ON tf.fstrcode_house=h.fstrcode_house AND tf.flat=f.flat2
                WHERE 1=1
                  AND h.fcomp_period LIKE '{$fcomp}-{$fperiod}'
                  AND h.prc_has_nachisl > 30
                  AND UPPER(h.street) LIKE UPPER('{$street}')
                  AND h.house2 LIKE UPPER('{$house}')
                  AND f.flat2 LIKE UPPER('{$flat}')
                  AND f.lcode LIKE '{$lcode}'
                ORDER BY h.street,h.house_order,f.flat_order,f.lcode
                ";
        }
        //wlog("\n\n$query1\n\n");
        $q = exec_query(tr($query1,"UTF-8","cp1251"));
        $i = 0;
        while( $row = $q->fetch_row() ){
            $i++;
            $objSheet->setCellValue("A".($i+2),(string)trq($row[0]));
            $objSheet->setCellValue("B".($i+2),(string)trq($row[1]));
            $objSheet->setCellValue("C".($i+2),(string)trq($row[2]));
            $objSheet->setCellValue("D".($i+2),(string)trq($row[3]));
            $objSheet->setCellValue("E".($i+2),(string)trq($row[4]));
            $objSheet->setCellValue("F".($i+2),(string)trq($row[5]));
            $objSheet->setCellValue("G".($i+2),(string)trq($row[6]));
        }
        wlog("list2 rows: $i \n");
    }
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($out_file);
}

//импорт данных из файлов типа "Помещения и ЕЛС от хх.хх.2016 хх-хх_Результат.xls"
//тут берем только поля B и D со второго листа
function import_lc_from_elc($p){
    $in_file = $p->fvar("from");
    $fcomp   = $p->fvar("fcomp");

    check_arg_error("from",$in_file);
    check_arg_error("fcomp",$fcomp);
    
    wlog("выборка всех записей лс из файла:\n");
    $objPHPExcel = PHPExcel_IOFactory::load($in_file);
    $objPHPExcel->setActiveSheetIndex(1);
    $objSheet = $objPHPExcel->getActiveSheet();
    
    $s = $objSheet->toArray(null,true,true,true);

    $arr = array();
    $i_row = 0;
    foreach($s as $n=>$row){
        $i_row++;
        if($i_row%10==0) echo ".";
        if($i_row<3) continue;
        array_push($arr,array('lcode'=>$row['B'],'gis_idjku'=>$row['D']));
    }
    wlog("\nвсего записей: $i_row\n");
    
    import_lc($p,$arr);
    
    //my_var_dump_html2("\$d",$d);
}

//импорт данных из файлов результата обработки "Шаблон импорта ЛС-10.0.2.1__мира_4а__Результат.xlsx"
//тут берем только поля B и C с первого листа
function import_lc_from_result($p){
    $in_file = $p->fvar("from");
    $fcomp   = $p->fvar("fcomp");

    check_arg_error("from",$in_file);
    check_arg_error("fcomp",$fcomp);
    
    wlog("выборка всех записей лс из файла:\n");
    $objPHPExcel = PHPExcel_IOFactory::load($in_file);
    $objPHPExcel->setActiveSheetIndex(0);
    $objSheet = $objPHPExcel->getActiveSheet();
    
    $s = $objSheet->toArray(null,true,true,true);

    $arr = array();
    $i_row = 0;
    foreach($s as $n=>$row){
        $i_row++;
        if($i_row%10==0) echo ".";
        if($i_row<3) continue;
        array_push($arr,array('lcode'=>$row['B'],'gis_idjku'=>$row['C']));
    }
    wlog("\nвсего записей: $i_row\n");
    
    import_lc($p,$arr);
    
    //my_var_dump_html2("\$d",$d);
}

//проверка и загрузка данных по лс в бд
function import_lc($p,$arr){
    $fcomp   = $p->fvar("fcomp");
    check_arg_error("fcomp",$fcomp);
    
    $fcomp_len = strlen($fcomp);
    
    $d = array();
    
    $i_row = 0;
    $i_cnt_lc_rows = 0;
    $i_need_load = 0;
    $cntNull_gis_idjku = 0;
    wlog("проверка лицевых счетов(".count($arr).") перед загрузкой в бд:\n");
    foreach($arr as $n=>$row){
        $i_row++;
        if($i_row%10==0) echo ".";
        
        $lcode = $row['lcode'];
        $gis_idjku = $row['gis_idjku'];
        
        $lcode_len = strlen($lcode);
        
        if($gis_idjku==""){
            $cntNull_gis_idjku++;
            continue;
        }
        
        if(substr($lcode,0,$fcomp_len)!=$fcomp && $fcomp!=2){
            wlog("ОШИБКА: возможно неверно задана УК($fcomp), проверьте загружаемый файл! row: {$i_row}; lcode: {$lcode}\n");
            exit;
        }
        
        if($fcomp==2 && $lcode_len!=4){
            wlog("ОШИБКА: возможно неверная длина лс($lcode_len) для УК($fcomp), проверьте загружаемый файл! row: {$i_row}; lcode: {$lcode}\n");
            exit;
        }
        
        //проверяем наличие этого лс в нашей базе
        $query1 = "
                SELECT 
                  COUNT(*),
                  (SELECT COUNT(*) FROM t_gis_lc t WHERE t.fulllcode='{$lcode}') AS cnt1, 
                  (SELECT COUNT(*) FROM t_gis_lc t WHERE t.gis_idjku='{$gis_idjku}') AS cnt2,
                  (SELECT COUNT(*) FROM t_gis_lc t WHERE t.gis_idjku='{$gis_idjku}' AND t.fulllcode='{$lcode}') AS cnt3  -- //загружен ли уже этот лицевой счет в нашу базу
                FROM T_KV2_UK_LAST_PERIOD a
                    LEFT JOIN t_kv2_nachisl_info_house h ON h.fcomp_period = a.fcomp||'-'||a.fperiod
                    LEFT JOIN t_kv2_nachisl_info f ON f.fcpsh=h.fcpsh
                WHERE 1=1
                  AND a.fcomp = {$fcomp}
                  AND IIF(h.fcomp = 2, f.lcode, h.fcomp||f.lcode) = '{$lcode}'
                ";
        $q = exec_query(tr($query1,"UTF-8","cp1251"));
        $row = $q->fetch_row();
        if($row[0]==0){
            wlog("ОШИБКА: лицевой счет \"{$lcode}\" в нашей БД не существует, проверьте загружаемый файл! row: {$i_row}; lcode: {$lcode}\n");
            wlog($query1);
            exit;
        }
        
        if($row[1]>0 && $row[2]>0 && $row[3]==0){
            wlog("ОШИБКА: lcode:\"{$lcode}\" и gis_idjku:\"{$gis_idjku}\" уже были ранее загружены, но соответствуют разным лицевым счетам!!! row: {$i_row}; lcode: {$lcode}\n");
            exit;
        }
        
        if($row[1]>0 && $row[2]==0){
            wlog("ОШИБКА: lcode:\"{$lcode}\" был ранее загружен, но соответствует другому gis_idjku!!! row: {$i_row}; lcode: {$lcode}\n");
            exit;
        }
        
        if($row[1]==0 && $row[2]>0){
            wlog("ОШИБКА: gis_idjku:\"{$gis_idjku}\" был ранее загружен, но соответствует другому lcode!!! row: {$i_row}; lcode: {$lcode}\n");
            exit;
        }
        
        $i_cnt_lc_rows++;
        if($row[3]>0){
            continue;
        }
        
        $i_need_load++;
        array_push($d, array('lcode'=>$lcode,'gis_idjku'=>$gis_idjku));
    }
    wlog("\nвыбрано лс для загрузку в бд / всего записей лс: $i_need_load / $i_cnt_lc_rows\n");
    if($cntNull_gis_idjku>0){
        wlog("ВНИМАНИЕ: по {$cntNull_gis_idjku} записям не указаны идентификаторы ГИСа. эти лс не загружены в ГИС!\n");
    }
    
    if($i_need_load>0){
        $i_row = 0;
        wlog("загружаем лс($i_need_load) в бд:\n");
        foreach($d as $n=>$a){
            $i_row++;
            $fulllcode = $a['lcode'];
            $lcode = substr($fulllcode,$fcomp_len);
            if($fcomp==2) $lcode = $fulllcode;
            $gis_idjku = $a['gis_idjku'];        
            $query1 = "INSERT INTO t_gis_lc(lcode,fcomp,fulllcode,gis_idjku)
                       VALUES('$lcode',$fcomp,'$fulllcode','$gis_idjku')
                      ";
            exec_query(tr($query1,"UTF-8","cp1251"));
            if($i_row%10==0){
                echo ".";
                exec_query("COMMIT");
            }
        }
        exec_query("COMMIT");
        wlog("\nзагрузка завершена, загружено $i_row лс\n");
    }else{
        wlog("нет новых данных для загрузки!\n");
    }
}
