<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<?php include_once "__public.php" ?>
<?php include_once "mCDatabase.php" ?>

<?php $title = "PHP / SQLServer" ?>
<title><?php echo $title ?></title>
<?php echo $title ?>
<table border="1" cellspacing="5" cellpadding="5">
<?php
$db = new mCDatabase();
$db->OpenDb( filetojson("_sqlserver.json") );

// 增
// Insert
{
	$rs = $db->OpenRs();
		
	$rs->SetInt("m_tinyint", 127);
	$rs->SetInt("m_smallint", 32767);
	$rs->SetInt("m_int", 2147483647);
	$rs->SetInt("m_bigint", 2147483647);
	
	$rs->SetDouble("m_double", 21474.83647);
	
	$rs->SetString("m_char5", "12345");
	$rs->SetString("m_varchar5", "12345");
	$rs->SetString("m_text", $title);
	
	$rs->SetDateTime("m_datetime", "");
	
	$rs->Insert("mctable");
	
	$db->CloseRs($rs);
}

// 删
// Delete
{
	$rs = $db->OpenRs();
		
	$rs->SetWhere("ID >= 77 and ID <= 85");
	$rs->Delete("mctable");
	
	$db->CloseRs($rs);
}

// 改
// Update
{
	$rs = $db->OpenRs();
	
	$rs->SetInt("m_bigint", 88);
	$rs->SetWhere("ID >= 77 and ID <= 88");
	$rs->Update("mctable");
	
	$db->CloseRs($rs);
}

// 查
// Query
{
	$table = "";

	$rs = $db->OpenRs();
	$rs->Query("select top 10 *,CONVERT(varchar(100), m_datetime, 20) as m_datetimeF from mctable order by ID desc");
	
	$table .= "<tr>";
	$iColumnCount = $rs->GetColumnCount();
	for($i = 0; $i < $iColumnCount; $i++)
	{
		$table .= "<td>";
		$table .= $rs->GetColumnName($i);
		$table .= "</td>";
	}
	$table .= "</tr>";
	
	while(!$rs->eof)
	{
		$table .= "<tr>";
		$table .= "<td>" . $rs->GetInt("ID") . "</td>";
		
		$table .= "<td>" . $rs->GetInt("m_tinyint") . "</td>";
		$table .= "<td>" . $rs->GetInt("m_smallint") . "</td>";
		$table .= "<td>" . $rs->GetInt("m_int") . "</td>";
		$table .= "<td>" . $rs->GetInt("m_bigint") . "</td>";
		
		$table .= "<td>" . $rs->GetDouble("m_double") . "</td>";
		
		$table .= "<td>" . $rs->GetString("m_char5") . "</td>";
		$table .= "<td>" . $rs->GetString("m_varchar5") . "</td>";
		$table .= "<td>" . $rs->GetString("m_text") . "</td>";
		
		$table .= "<td>" . $rs->GetDateTime("m_datetime") . "</td>";
		$table .= "<td>" . $rs->GetString("m_datetimeF") . "</td>";
		$table .= "</tr>";
		
		$rs->MoveNext();
	}
	$db->CloseRs($rs);
	
	echo $table;
}

$db->CloseDb();
?>
</table>
