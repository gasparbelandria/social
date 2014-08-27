<?php
class DB_mysql{
    
    /**
     * Conexion a la base de datos
     */
    
     function conectar(){
		 /* gasparbelandria.com */
         $this -> BaseDatos = ""; 
         $this -> Servidor 	= "";  
         $this -> Usuario 	= ""; 
         $this -> Clave 	= "";  

         // Conectamos al servidor
        $this -> Conexion_ID = mysql_connect($this -> Servidor, $this -> Usuario, $this -> Clave);
		if ( (mysql_errno() == 1203) || (!$this -> Conexion_ID) || (!@mysql_select_db($this -> BaseDatos, $this -> Conexion_ID)) ){
			// 1203 == ER_TOO_MANY_USER_CONNECTIONS (mysqld_error.h)
			header("Location: http://www.gasparbelandria.com/");
			exit;
		}else{
			mysql_query("SET NAMES 'utf8'");
			return $this -> Conexion_ID;
		}
	}
	
	function desconectar(){
		mysql_close($this -> Conexion_ID);
	}
    /**
     * identificador de conexi�n y consulta
     */

     var $Conexion_ID = 0;
     var $Consulta_ID = 0;
    
    /**
     * numero de error y texto error
     */

     var $Errno = 0;
     var $Error = "";

    /**
     * Ejecuta un consulta
     */
    
	function consulta($sql = ""){
		if ($sql == ""){
			$this -> Error = "No ha especificado una consulta SQL";	
			return 0;
		}
		// ejecutamos la consulta
		$this -> Consulta_ID = @mysql_query($sql, $this -> Conexion_ID);
		if (!$this -> Consulta_ID){
			$this -> Errno = mysql_errno();
			$this -> Error = mysql_error();
		}
		return $this -> Consulta_ID;
	}	

	function totalregistro($sql){
		$this -> Total_ID = @mysql_num_rows($sql);
		return $this -> Total_ID;
	}
    /**
     * Devuelve el numero de registros de una consulta
     */
    
	function disponibilidad(){
		return mysql_num_rows($this -> Consulta_ID);
	} 
		
	function validasession (){
		session_start();
		if (!isset($_SESSION['expira'])){
			$_SESSION['expira']=time();		
		}else{
			// busca la cantidad de segundos entre la ultima session y el tiempo actual
			$t = time()-$_SESSION['expira'];
			if ($t>30){
				echo "<hr>session destruida por favor vuelva a ingresar sus datos de seguridad";
				session_destroy();
			}
		}		
	}
}

?>