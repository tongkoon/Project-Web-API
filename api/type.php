<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request; 

$app->get('/type', function (Request $request, Response $response, array $args) {
    $conn = $GLOBALS["dbconn"];
    $sql = "select * from type";
    $result = $conn->query($sql);
    $data = array();
    while($row = $result->fetch_assoc()){
        array_push($data,$row);
    }
    
    $json = json_encode($data);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

$app->get('/type/search/{fid}', function (Request $request, Response $response, array $args) {
    $conn = $GLOBALS["dbconn"];
    $fid = $args["fid"];
    $sql = "select type.tid,name from type,food_type where type.tid = food_type.tid and fid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$fid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $json = json_encode($row);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

// Insert
$app->post('/type/insert', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body,true);

    $conn = $GLOBALS["dbconn"];
    $sql = "select * from type where name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$bodyArr["name"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $affected_rows = $result->num_rows;
    if($affected_rows == 0){
        $sql = "insert into type (tid,name) values(?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is",$bodyArr["tid"],$bodyArr["name"]);
        $stmt->execute();
        $value = array("status"=>'success');
        $json = json_encode($value);
        $response->getBody()->write($json);
    }
    else{
        $json = json_encode("Repeated name");
    }
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

//delete
$app->post('/type/delete', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body,true);

    $conn = $GLOBALS["dbconn"];
    $sql = "delete from type where tid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$bodyArr["tid"]);
    $stmt->execute();
    $value = array("status"=>'success');
    $json = json_encode($value);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

?>