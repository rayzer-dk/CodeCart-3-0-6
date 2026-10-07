<?php
require dirname(__DIR__) . '/upload/system/library/session.php';
final class ReviewSessionAdapter {
    public array $records = [];
    public string $oldId;
    public bool $failNew;
    public bool $throwNew = false;
    public function __construct(string $oldId, bool $failNew) { $this->oldId=$oldId; $this->failNew=$failNew; }
    public function read($id) { return $this->records[$id] ?? []; }
    public function write($id, $data) { if ($this->failNew && $id !== $this->oldId) { if ($this->throwNew) throw new RuntimeException('Storage unavailable'); return false; } $this->records[$id]=$data; return true; }
    public function destroy($id) { unset($this->records[$id]); return true; }
}
function fixture(bool $fail, bool $throw = false): array {
    $oldId=str_repeat('a',32);
    $adapter=new ReviewSessionAdapter($oldId,$fail);
    $adapter->throwNew = $throw;
    $adapter->records[$oldId]=['visitor'=>'anonymous'];
    $ref=new ReflectionClass(Session::class);
    $session=$ref->newInstanceWithoutConstructor();
    $property=$ref->getProperty('adaptor'); $property->setAccessible(true); $property->setValue($session,$adapter);
    $session->start($oldId);
    $session->data['user_id']=42;
    $session->data['user_token']='review-token';
    try { $session->regenerate(); } catch (Throwable $e) { echo 'Regeneration exception: ',get_class($e),PHP_EOL; }
    $session->close(); // Exact shutdown writer invoked by registered close callback.
    return [$session,$adapter,$oldId];
}
[$session,$adapter,$oldId]=fixture(true);
$unsafe=isset($adapter->records[$oldId]['user_id']);
echo 'FAILURE_PATH old_id_retained=',($session->getId()===$oldId?'yes':'no'),' authenticated_state_persisted=',($unsafe?'yes':'no'),PHP_EOL;
[$success,$successAdapter,$successOld]=fixture(false);
$positive=$success->getId()!==$successOld && !isset($successAdapter->records[$successOld]) && ($successAdapter->records[$success->getId()]['user_id']??0)===42;
echo 'SUCCESS_PATH rotated_authenticated_state=',($positive?'PASS':'FAIL'),PHP_EOL;
[$thrown,$thrownAdapter,$thrownOld]=fixture(true,true);
$thrownUnsafe=isset($thrownAdapter->records[$thrownOld]['user_id']);
echo 'THROWN_BACKEND authenticated_state_persisted=',($thrownUnsafe?'yes':'no'),PHP_EOL;
exit($unsafe || $thrownUnsafe || !$positive ? 1 : 0);
