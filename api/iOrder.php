<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request; 

// Search by oid -> *
$app->get('/iorder/oid/{oid}', function (Request $request, Response $response, array $args) {
    $oid = $args["oid"];
    $conn = $GLOBALS["dbconn"];
    $sql = "select * from iorder where oid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$oid);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $json = json_encode($data);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

// Search by CusID -> *
$app->get('/iorder/cusID/{cusID}', function (Request $request, Response $response, array $args) {
    $cusID = $args["cusID"];
    $conn = $GLOBALS["dbconn"];
    $sql = "select iorder.oid,name,DATE_FORMAT(odate,'%d-%m-%Y %H:%i:%s') as odate,
            DATE_FORMAT(fdate,'%d-%m-%Y %H:%i:%s') as fdate,phone,address,status 
            from iorder,history 
            where iorder.oid =  history.oid and cusID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$cusID);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $data = array();
    while($row = $result->fetch_assoc()){
        array_push($data,$row);
    }
    
    $json = json_encode($data);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

// get oid is status 2 (status are foods into cart)
$app->get('/iorder/getOid/{cid}', function (Request $request, Response $response, array $args) {
    $cid = $args["cid"];
    $conn = $GLOBALS["dbconn"];
    $status = 2;
    $sql = "select * from iorder where cusID = ? and status = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii",$cid,$status);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $value = array("oid"=>$data['oid']);
    $json = json_encode($value);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

// Insert
$app->get('/iorder/insert/{cid}', function (Request $request, Response $response, array $args) {
    $conn = $GLOBALS["dbconn"];
    $cid = $args["cid"];
    $status = 2;
    $sql = "insert into iorder (cusID,status) values(?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii",$cid,$status);
    $stmt->execute();
    $last_id = $conn->insert_id;
    $value = array("oid"=>$last_id);
    $json = json_encode($value);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

// delete
$app->post('/iorder/delete', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body,true);

    $conn = $GLOBALS["dbconn"];
    $sql = "delete from iorder where oid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$bodyArr["oid"]);
    $stmt->execute();
    $value = array("status"=>'success');
    $json = json_encode($value);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});


// update
$app->get('/iorder/customer/update/{oid}', function (Request $request, Response $response, array $args) {
    // ส่ง oid 
    $oid = $args['oid'];
    date_default_timezone_set('Asia/Bangkok');
    $odate = date('Y-m-d H:i:s');
    $status = 0;

    $conn = $GLOBALS["dbconn"];
    $sql = "update iorder set odate = ?,status = ? where oid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii",$odate,$status,$oid);
    $stmt->execute();
    $value = array("status"=>'success');
    $json = json_encode($value);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

$app->get('/iorder/admin/update/{oid}', function (Request $request, Response $response, array $args) {
    // ส่ง oid 
    $oid = $args['oid'];
    date_default_timezone_set('Asia/Bangkok');
    $fdate = date('Y-m-d H:i:s');
    $status = 1;

    $conn = $GLOBALS["dbconn"];
    $sql = "update iorder set fdate = ?,status = ? where oid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii",$fdate,$status,$oid);
    $stmt->execute();
    $value = array("status"=>'success');
    $json = json_encode($value);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

// iOrder total
$app->get('/iorder/{status}', function (Request $request, Response $response, array $args) {
    $conn = $GLOBALS["dbconn"];
    $status = $args['status'];
    $sql = "select iorder.oid,cid,name,
            DATE_FORMAT(odate,'%d-%m-%Y %H:%i:%s') as odate,
            DATE_FORMAT(fdate,'%d-%m-%Y %H:%i:%s') as fdate,phone,address,status from iorder,history 
            where iorder.oid = history.oid and status = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$status);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = array();
    while($row = $result->fetch_assoc()){
        array_push($data,$row);
    }
    
    $json = json_encode($data);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

// update status
$app->post('/iorder/update/status', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body,true);
    date_default_timezone_set('Asia/Bangkok');
    $fdate = date('Y-m-d H:i:s');
    // $response->getBody()->write(json_encode($fdate));
    $status = 1;
    $conn = $GLOBALS["dbconn"];
    $sql = "update iorder set fdate = ?, status = ? where oid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii",$fdate,$status,$bodyArr['oid']);
    $stmt->execute();
    $value = array("status"=>'success');
    $json = json_encode($value);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});


// history
// insert
$app->post('/history/insert', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body,true);

    $conn = $GLOBALS["dbconn"];
    $sql = "insert into history(oid,cid,name,phone,address) values(?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisss",$bodyArr['oid'],$bodyArr['cid'],$bodyArr['name'],$bodyArr['phone']
                       ,$bodyArr['address']);
    $stmt->execute();
    $value = array("status"=>'success');
    $json = json_encode($value);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

?>