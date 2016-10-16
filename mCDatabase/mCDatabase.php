<?php
// 作者: 李辙
// 找我: wakelee.coderwriter.com
// Author: Wake Lee
// FindMe: wakelee.coderwriter.com
class mCDatabase_base
{
	var $bLog = true;
	var $sLogPath = "";
	function SetLog($bLog, $sLogPath)
	{
		$this->bLog = $bLog;
		$this->sLogPath = $sLogPath;
	}
	
	function Show($tip)
	{
		$html = "";
		$html .= '<div style="background-color:#b22222;color:#ffffe0;font-size:16px;padding:10px;margin:10px 0px;">';
		$html .= $tip;
		$html .= '</div>';
		
		echo $html;
		
		if($this->bLog)
		{
			$sAbsolutePath = $_SERVER['DOCUMENT_ROOT'] . "/" . $this->sLogPath . "/mCDatabase-logs";
			if( !is_dir($sAbsolutePath) ) mkdir($sAbsolutePath);
			$f = fopen($sAbsolutePath . "/mCDatabase-log-" . date("Y-m-d") . ".txt", "a+");
			fwrite($f, "[ " . date("Y-m-d H:i:s") . " ] [ " . $this->GetUrl() . " ] [ " . $tip . " ]\r\n");
			fclose($f);
		}
	}
	
	var $ErrorCode = 0;
	function IsError($tip)
	{		
		switch($this->ErrorCode)
		{
			case 0: break;
			
			case 1: $this->Show("Error : open connection error " . $tip); break;
			case 2: $this->Show("Error : close connection error " . $tip); break;
			
			case 3: $this->Show("Error : open recordset error " . $tip); break;
			case 4: $this->Show("Error : close recordset error " . $tip); break;
			
			case 5: $this->Show("Error : insert error " . $tip); break;
			case 6: $this->Show("Error : delete error " . $tip); break;
			case 7: $this->Show("Error : update error " . $tip); break;
			case 8: $this->Show("Error : query error " . $tip); break;
			
			case 9: $this->Show("Error : get record total count error " . $tip); break;
			case 10: $this->Show("Error : get field total count error " . $tip); break;
			case 11: $this->Show("Error : get field name error " . $tip); break;
			
			case 12: $this->Show("Error : get int error " . $tip); break;
			case 13: $this->Show("Error : get double error " . $tip); break;
			case 14: $this->Show("Error : get string error " . $tip); break;
			case 15: $this->Show("Error : get datetime error " . $tip); break;
			
			case 16: $this->Show("Error : move next error " . $tip); break;
			
			case 17: $this->Show("Error : driver error " . $tip); break;
		}
		
		return $this->ErrorCode == 0 ? false : true;
	}
	
	public function GetSQLServerError()
	{
		$sqlerror = "";
		
		if( ( $errors = sqlsrv_errors() ) != null)
		{
			foreach($errors as $error) 
			{
				$sqlerror .= "SQLSTATE: " . $error["SQLSTATE"] . "<br/>";
				$sqlerror .= "code: " . $error["code"] . "<br/>";
				$sqlerror .= "message: " . $error["message"] . "<br/><br/>";
			}
		}
		
		return $sqlerror;
	}
	
	function GetUrl()
	{
		$host = $_SERVER["HTTP_HOST"];
		$url = $_SERVER["URL"];
		$str = $_SERVER["QUERY_STRING"];
		
		$full_url = "http://" . $host . $url;
		if($str != "") $full_url .= "?" . $str;
	
		return $full_url;
	}
}

// 记录集类
// Recordset class
class CRs extends mCDatabase_base
{
	public $dbtype;
	public $conn;
	public $stmt = false;
	public $rs;
	public $eof;

	public $kvs = array(); // key/value array
	public $where;
	
	public function __construct($dbtype, $conn, $ErrorCode, $bLog, $sLogPath)
	{
		$this->dbtype = $dbtype;
		$this->conn = $conn;
		$this->ErrorCode = $ErrorCode;
		$this->bLog = $bLog;
		$this->sLogPath = $sLogPath;
		$this->eof = true;
	}
    public function __destruct()
	{
	}
	
	// 增
	// Insert
	public function Insert($TableName)
	{
		if( $this->IsError("Insert()") ) return;

		switch($this->dbtype)
		{
			case "mysql":
			{				
				$sql = "insert into " . $TableName;
				$sql .= "(";
				$count = count($this->kvs);
				for($i = 0; $i < $count; $i++)
				{
					$sql .= $this->kvs[$i]->key;
					if($i != $count - 1) $sql .= ",";
				}
				$sql .= ")";
				$sql .= " values ";
				$sql .= "(";
				$count = count($this->kvs);
				for($i = 0; $i < $count; $i++)
				{
					switch( gettype($this->kvs[$i]->value) )
					{
						case "integer":
						{
							$sql .= $this->kvs[$i]->value;
						}
						break;
						
						case "string":
						{
							$sql .= "'" . $this->kvs[$i]->value . "'";
						}
						break;
					}
					if($i != $count - 1) $sql .= ",";
				}
				$sql .= ")";
								
				$this->stmt = mysqli_query($this->conn, $sql);
				if($this->stmt === false)
				{
					$this->ErrorCode = 5;
					if( $this->IsError( "Insert() " . mysqli_error($this->conn) ) ) return;
				}
			}
			break;
			
			case "sqlserver":
			{				
				$sql = "insert into " . $TableName;
				$sql .= "(";
				$count = count($this->kvs);
				for($i = 0; $i < $count; $i++)
				{
					$sql .= $this->kvs[$i]->key;
					if($i != $count - 1) $sql .= ",";
				}
				$sql .= ")";
				$sql .= "values";
				$sql .= "(";
				$count = count($this->kvs);
				for($i = 0; $i < $count; $i++)
				{
					$sql .= "?";
					if($i != $count - 1) $sql .= ",";
				}
				$sql .= ")";
				
				$values = array();
				$count = count($this->kvs);
				for($i = 0; $i < $count; $i++)
				{
					array_push($values, $this->kvs[$i]->value);
				}

				$this->stmt = sqlsrv_query($this->conn, $sql, $values);
				if($this->stmt === false)
				{
					$this->ErrorCode = 5;
					if( $this->IsError( "Insert() " . $this->GetSQLServerError() ) ) return;
				}
			}
			break;
		}
	}
	
	// 删
	// Delete
	public function Delete($TableName)
	{
		if( $this->IsError("Delete()") ) return;
		
		switch($this->dbtype)
		{
			case "mysql":
			{				
				$sql = "delete from " . $TableName . " where " . $this->where;
				
				$this->stmt = mysqli_query($this->conn, $sql);
				if($this->stmt === false)
				{
					$this->ErrorCode = 6;
					if( $this->IsError( "Delete() " . mysqli_error($this->conn) ) ) return;
				}
			}
			break;
			
			case "sqlserver":
			{				
				$sql = "delete from " . $TableName . " where " . $this->where;

				$this->stmt = sqlsrv_query($this->conn, $sql);
				if($this->stmt === false)
				{
					$this->ErrorCode = 6;
					if( $this->IsError( "Delete() " . $this->GetSQLServerError() ) ) return;
				}
			}
			break;
		}
	}
	
	// 改
	// Update
	public function Update($TableName)
	{
		if( $this->IsError("Update()") ) return;
		
		switch($this->dbtype)
		{
			case "mysql":
			{				
				$sql = "update " . $TableName . " set ";
				$count = count($this->kvs);
				for($i = 0; $i < $count; $i++)
				{
					switch( gettype($this->kvs[$i]->value) )
					{
						case "integer":
						{
							$sql .= $this->kvs[$i]->key . "=" . $this->kvs[$i]->value;
						}
						break;
						
						case "string":
						{
							$sql .= $this->kvs[$i]->key . "=" . "'" . $this->kvs[$i]->value . "'";
						}
						break;
					}
					if($i != $count - 1) $sql .= ",";
				}
				$sql .= " where " . $this->where;
												
				$this->stmt = mysqli_query($this->conn, $sql);
				if($this->stmt === false)
				{
					$this->ErrorCode = 7;
					if( $this->IsError( "Update() " . mysqli_error($this->conn) ) ) return;
				}
			}
			break;
			
			case "sqlserver":
			{				
				$sql = "update " . $TableName . " set ";
				$count = count($this->kvs);
				for($i = 0; $i < $count; $i++)
				{
					switch( gettype($this->kvs[$i]->value) )
					{
						case "integer":
						{
							$sql .= $this->kvs[$i]->key . "=" . $this->kvs[$i]->value;
						}
						break;
						
						case "string":
						{
							$sql .= $this->kvs[$i]->key . "=" . "'" . $this->kvs[$i]->value . "'";
						}
						break;
					}
					if($i != $count - 1) $sql .= ",";
				}
				$sql .= " where " . $this->where;
				
				$this->stmt = sqlsrv_query($this->conn, $sql);
				if($this->stmt === false)
				{
					$this->ErrorCode = 7;
					if( $this->IsError( "Update() " . $this->GetSQLServerError() ) ) return;
				}
			}
			break;
		}
	}

	// 查
	// Query
	public function Query($sql)
	{
		if( $this->IsError("Query()") ) return;

		switch($this->dbtype)
		{
			case "mysql":
			{
				$this->stmt = mysqli_query($this->conn, $sql);
				if($this->stmt === false)
				{
					$this->ErrorCode = 8;
					if( $this->IsError( "Query() " . mysqli_error($this->conn) ) ) return;
				}
				
				$this->rs = mysqli_fetch_array($this->stmt, MYSQLI_ASSOC);
				$this->eof = $this->rs ? false : true;
			}
			break;
			
			case "sqlserver":
			{
				$this->stmt = sqlsrv_query($this->conn, $sql);
				if($this->stmt === false)
				{
					$this->ErrorCode = 8;
					if( $this->IsError( "Query() " . $this->GetSQLServerError() ) ) return;
				}
				
				$this->rs = sqlsrv_fetch_array($this->stmt, SQLSRV_FETCH_ASSOC);
				$this->eof = $this->rs ? false : true;
			}
			break;
		}
	}
	
	function GetRecordCount()
	{
		$value = 0;
		
		if( $this->IsError("GetRecordCount()") ) return $value;

		switch($this->dbtype)
		{
			case "mysql":
			{
				$value = mysqli_num_rows($this->stmt);
			}
			break;
			
			case "sqlserver":
			{
				$value = sqlsrv_num_rows($this->stmt);
			}
			break;
		}

		return $value;
	}
	function GetColumnCount()
	{
		$value = 0;
		
		if( $this->IsError("GetColumnCount()") ) return $value;

		$value = count($this->rs);

		return $value;
	}
	function GetColumnName($index)
	{
		$value = "";
		
		if( $this->IsError("GetColumnName()") ) return $value;

		$keys = array_keys($this->rs);
		$value = $keys[$index];

		return $value;
	}
		
	public function SetInt($key, $value)
	{
		$kv = json_decode("{}");
		$kv->key = $key;
		$kv->value = $value;
		array_push($this->kvs, $kv);
	}
	public function SetDouble($key, $value)
	{
		$kv = json_decode("{}");
		$kv->key = $key;
		$kv->value = "" . $value;
		array_push($this->kvs, $kv);
	}
	public function SetString($key, $value)
	{
		$kv = json_decode("{}");
		$kv->key = $key;
		$kv->value = $value;
		array_push($this->kvs, $kv);
	}
	public function SetDateTime($key, $value)
	{
		if($value == "") $value = strftime( "%Y-%m-%d %H:%M:%S", time() );
		
		$kv = json_decode("{}");
		$kv->key = $key;
		$kv->value = $value;
		array_push($this->kvs, $kv);
	}
	
	public function GetInt($key)
	{
		$value = 0;
		
		if( $this->IsError("GetInt()") ) return $value;

		$value = intval( $this->rs[$key] );

		return $value;
	}
	public function GetDouble($key)
	{
		$value = 0;
		
		if( $this->IsError("GetDouble()") ) return $value;

		$value = doubleval( $this->rs[$key] );

		return $value;
	}
	public function GetString($key)
	{
		$value = "";
		
		if( $this->IsError("GetString()") ) return $value;

		$value = $this->rs[$key];

		return $value;
	}
	public function GetDateTime($key)
	{
		$value = "";
		
		if( $this->IsError("GetString()") ) return $value;

		switch($this->dbtype)
		{
			case "mysql":
			{
				$value = $this->rs[$key];
			}
			break;
			
			case "sqlserver":
			{
				$value = $this->rs[$key]->format("Y-m-d H:i:s");
			}
			break;
		}

		return $value;
	}
	
	public function SetWhere($where)
	{
		$this->where = $where;;
	}
	
	public function MoveNext()
	{
		switch($this->dbtype)
		{
			case "mysql":
			{
				$this->rs = mysqli_fetch_array($this->stmt);
			}
			break;
			
			case "sqlserver":
			{
				$this->rs = sqlsrv_fetch_array($this->stmt);
			}
			break;
		}
		$this->eof = $this->rs ? false : true;
	}
}
class mCDatabase extends mCDatabase_base
{
	public $dbtype;
	public $conn;
	
	// 打开数据库
	// Open database
	public function OpenDb($option)
	{
		$this->dbtype = $option->dbtype;
		
		switch($this->dbtype)
		{
			case "mysql":
			{
				$this->conn = mysqli_connect($option->dblocation, $option->uid, $option->pwd, $option->dbname);
				
				if( mysqli_connect_errno($this->conn) )
				{
					$this->ErrorCode = 1;
					if( $this->IsError( "OpenDb() " . iconv( 'gbk', 'utf-8', mysqli_connect_error() ) ) ) return;
				}
				
				mysqli_set_charset($this->conn, $option->dbcharset);
			}
			break;
			
			case "sqlserver":
			{
				$this->conn = sqlsrv_connect
				( 
					$option->dblocation, 
					array
					(
						"UID"=>$option->uid,
						"PWD"=>$option->pwd,
						"Database"=>$option->dbname,
						"CharacterSet"=>$option->dbcharset
					)
				);
				if($this->conn === false)
				{
					$this->ErrorCode = 1;
					if( $this->IsError("OpenDb()") ) return;
				}				
			}
			break;
		}
	}

	// 关闭数据库
	// Close database
	public function CloseDb()
	{
		if( $this->IsError("CloseDb()") ) return;

		switch($this->dbtype)
		{
			case "mysql":
			{
				if( mysqli_close($this->conn) === false )
				{
					$this->ErrorCode = 2;
					if( $this->IsError("CloseDb()") ) return;
				}
			}
			break;
			
			case "sqlserver":
			{
				if( sqlsrv_close($this->conn) === false )
				{
					$this->ErrorCode = 2;
					if( $this->IsError("CloseDb()") ) return;
				}
			}
			break;
		}
	}
	
	// 打开记录集
	// Open recordset
	public function OpenRs()
	{
		$rs = new CRs($this->dbtype, $this->conn, $this->ErrorCode, $this->bLog, $this->sLogPath);
		
		if( $this->IsError("OpenRs()") ) return $rs;
				
		return $rs;
	}

	// 关闭记录集
	// Close recordset
	public function CloseRs($rs)
	{
		if( $this->IsError("CloseRs()") ) return;

		switch($this->dbtype)
		{
			case "mysql":
			{
				if( ( gettype($rs->stmt) != "boolean" ) && ($rs->stmt != false) )
				{
					mysqli_free_result($rs->stmt);
				}
			}
			break;
			
			case "sqlserver":
			{
				if( !($rs->stmt === false) )
				{
					if( sqlsrv_free_stmt($rs->stmt) === false )
					{
						$this->ErrorCode = 4;
						if( $this->IsError("CloseDb()") ) return;
					}
				}
			}
			break;
		}
	}
}
?>