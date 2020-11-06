<?php

echo "file: ".$argv[1]."\n";


//pasring_based on
//https://ideone.com/0YAVfY
//https://forum.skodahome.cz/topic/123830-podtlakova-trubka-brzdoveho-valca/
//https://strserg.com/VAG_dataflash/


$dump_file = false;
if(isset($argv[2]) && $argv[2] == 'dump'){
	$dump_file = true;
}

$fh = fopen($argv[1], 'r');

$fdump = false;
if($dump_file){
	$fdump = fopen($argv[1].'.bin', 'w');
}

$header = array();
$header['sgoLabel'] = fread($fh, 17);
$header['SGO_COMPATIBILITY'] = read_int($fh);
$header['checksum'] = read_int($fh);

//Where in the file are diffrent structures located
$header['indexIDENT'] = read_int($fh);
$header['indexbaudrate_2000'] = read_int($fh);
$header['indexkwp_2000_rei'] = read_int($fh);
$header['indexkwp_2000_acp'] = read_int($fh);
$header['indexkwp_2000_sa2'] = read_int($fh);
$header['index_DATA_BLOCKS'] = read_int($fh);

//Read Ident data
fseek($fh, $header['indexIDENT']);
$header['cnt_file'] = (xorstr(fread($fh, 260)));
$header['sw_version_kurz_String'] = xorstr((fread($fh, 5)));
$header['sw_version_kurz_DWord'] = read_int($fh);

//Read sa2 data
fseek($fh, $header['indexkwp_2000_sa2']);
$header['SGO_KWP_2000_SA2_nritems'] = read_int($fh);
$header['SGO_KWP_2000_SA2'] = strToHex((fread($fh, $header['SGO_KWP_2000_SA2_nritems'])));

//Read acctual data blocks header
fseek($fh, $header['index_DATA_BLOCKS']);
$header['SGO_DATENBLOCKE_nritems'] = read_int($fh);
$header['SGO_DATENBLOCKE'] = array();
for($i=0;$i<$header['SGO_DATENBLOCKE_nritems'];$i++){
	$header['SGO_DATENBLOCKE'][] = read_int($fh);
}

$data_blocks = array();
//Read acctual data blocks
foreach($header['SGO_DATENBLOCKE'] AS $block_index){

	fseek($fh, $block_index);
	$block = array();
	$block['start_adr'] = read_3byte_adr($fh);
	$block['data_block_format'] = strToHex((fread($fh, 1)));
	$block['size_after_decompression'] = read_3byte_adr($fh);
	$block['SGO_LOESCH_BEREICH_start_adr'] = read_3byte_adr($fh);
	$block['SGO_LOESCH_BEREICH_end_adr'] = read_3byte_adr($fh);
	$block['SGO_DATENBLOCK_CHECK_start_adr'] = read_3byte_adr($fh);
	$block['SGO_DATENBLOCK_CHECK_end_adr'] = read_3byte_adr($fh);
	$block['SGO_DATENBLOCK_CHECK_checksum'] = strToHex((fread($fh, 2)));

	$block['SGO_DATENBLOCK_datenblock_daten_size'] = read_int($fh);

	$data = fread($fh, $block['SGO_DATENBLOCK_datenblock_daten_size']);
	//$block['SGO_DATENBLOCK_data'] = strToHex($data);
	if($block['size_after_decompression'] != $block['SGO_DATENBLOCK_datenblock_daten_size']){
		throw new Exception("commrepssion used");
	}

	if($fdump){
		fseek($fdump, $block['start_adr']);
		fwrite($fdump, $data);
	}

	//$block[''] = strToHex((fread($fh, 1)));
	$data_blocks[] = $block;

}

var_dump($header);
if($fdump){
	fclose($fdump);
	echo "dumped file\n";
}else{
	var_dump($data_blocks);
}

function read_3byte_adr($fh){
	$start_adr = fread($fh, 3);
	$start_adr = $start_adr[2].$start_adr[1].$start_adr[0]."\0";
	return unpack('V', $start_adr)[1];
}
function read_int($fh){
	return unpack('V', fread($fh, 4))[1];
}

function xorstr($string){
	$str = '';
    for ($i=0; $i<strlen($string); $i++){
		$c = ord($string[$i]);
		if($c == 0){
			break;
		}
		$str .= chr($c^0xff);
	}
	return $str;
}

function strToHex($string){
    $hex = '';
    for ($i=0; $i<strlen($string); $i++){
        $ord = ord($string[$i]);
        $hexCode = dechex($ord);
        $hex .= substr('0'.$hexCode, -2).' ';
    }
    return strToUpper($hex);
}
