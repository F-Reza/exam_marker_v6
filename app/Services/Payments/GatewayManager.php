<?php
namespace App\Services\Payments;
use App\Models\{PaymentGatewaySetting,PaymentTransaction};
use Illuminate\Support\Facades\Http;
use RuntimeException;
class GatewayManager {
 public const SUPPORTED=['paypal','payu'];
 public function enabled():array{return PaymentGatewaySetting::where('enabled',true)->whereIn('provider',self::SUPPORTED)->orderBy('label')->get()->filter(fn($gateway)=>$gateway->provider!=='paypal'||config('exam-marker.currency','INR')!=='INR')->all();}
 public function begin(PaymentTransaction $tx,PaymentGatewaySetting $gateway):array{
  $this->supported($gateway->provider);
  if($gateway->provider==='payu')return $this->payuBegin($tx,$gateway);
  $token=$this->token($gateway);
  $response=Http::timeout(30)->withToken($token)->withHeaders(['PayPal-Request-Id'=>'create-'.$tx->reference])->post($this->base($gateway).'/v2/checkout/orders',[
   'intent'=>'CAPTURE','purchase_units'=>[['reference_id'=>$tx->reference,'custom_id'=>$tx->reference,'amount'=>['currency_code'=>$tx->currency,'value'=>number_format((float)$tx->amount,2,'.','')]]],
   'application_context'=>['return_url'=>route('payments.callback',['provider'=>'paypal','tx'=>$tx->reference]),'cancel_url'=>route('payments.cancel',['reference'=>$tx->reference])],
  ]);
  $response->throw();$id=$response->json('id');$url=collect($response->json('links',[]))->firstWhere('rel','approve')['href']??null;
  if(!is_string($id)||!is_string($url)||!str_starts_with($url,'https://'))throw new RuntimeException('PayPal did not return a valid checkout order.');
  return ['type'=>'redirect','url'=>$url,'provider_reference'=>$id,'payload'=>$response->json()];
 }
 public function verify(string $provider,array $payload,PaymentGatewaySetting $gateway,?PaymentTransaction $tx=null):array{
  $this->supported($provider);
  if($provider==='payu')return $this->payuVerify($payload,$gateway,$tx);
  if(!$tx||$provider!==$tx->gateway||$gateway->provider!==$tx->gateway||!is_string($payload['token']??null)||!hash_equals((string)$tx->provider_reference,$payload['token']))throw new RuntimeException('Payment order does not match this transaction.');
  $order=rawurlencode($tx->provider_reference);$token=$this->token($gateway);$base=$this->base($gateway).'/v2/checkout/orders/'.$order;
  $response=Http::timeout(30)->withToken($token)->get($base);$response->throw();$data=$response->json();
  if(($data['id']??null)!==$tx->provider_reference||count($data['purchase_units']??[])!==1)throw new RuntimeException('Provider returned a different payment order.');
  $unit=$data['purchase_units'][0];
  if(($unit['reference_id']??null)!==$tx->reference||!$this->amount($unit['amount']??[],$tx))throw new RuntimeException('Payment reference, amount or currency does not match.');
  if(($data['status']??'')==='APPROVED'){
   $response=Http::timeout(30)->withToken($token)->withHeaders(['Content-Type'=>'application/json','PayPal-Request-Id'=>'capture-'.$tx->reference])->post($base.'/capture');
   $response->throw();$data=$response->json();
  }
  $capture=$data['purchase_units'][0]['payments']['captures'][0]??[];
  $paid=($data['id']??null)===$tx->provider_reference&&($data['status']??null)==='COMPLETED'&&($capture['status']??null)==='COMPLETED'&&$this->amount($capture['amount']??[],$tx);
  return ['paid'=>$paid,'reference'=>$tx->provider_reference,'payload'=>$data];
 }
 private function amount(array $amount,PaymentTransaction $tx):bool{return ($amount['currency_code']??null)===$tx->currency&&is_numeric($amount['value']??null)&&(int)round((float)$amount['value']*100)===(int)round((float)$tx->amount*100);}
 private function payuBegin(PaymentTransaction $tx,PaymentGatewaySetting $gateway):array{
  $credentials=$gateway->credentials;if(empty($credentials['merchant_key'])||empty($credentials['salt'])||$tx->currency!=='INR')throw new RuntimeException('PayU requires INR pricing and configured merchant key/salt.');
  $fields=['key'=>$credentials['merchant_key'],'txnid'=>$tx->reference,'amount'=>number_format((float)$tx->amount,2,'.',''),'productinfo'=>$tx->plan->name,'firstname'=>$tx->user->name,'email'=>$tx->user->email,'phone'=>$tx->user->mobile??''];
  $fields['hash']=hash('sha512',implode('|',array_merge(array_values(array_intersect_key($fields,array_flip(['key','txnid','amount','productinfo','firstname','email']))),array_fill(0,10,''),[$credentials['salt']])));
  $fields['surl']=$fields['furl']=route('payments.callback',['provider'=>'payu','tx'=>$tx->reference]);
  return ['type'=>'form','url'=>$gateway->environment==='live'?'https://secure.payu.in/_payment':'https://test.payu.in/_payment','fields'=>$fields,'provider_reference'=>$tx->reference,'payload'=>['checkout'=>$fields]];
 }
 private function payuVerify(array $payload,PaymentGatewaySetting $gateway,?PaymentTransaction $tx):array{
  $credentials=$gateway->credentials;$key=$credentials['merchant_key']??'';$salt=$credentials['salt']??'';
  if(!$tx||$tx->gateway!=='payu'||$gateway->provider!=='payu'||$tx->currency!=='INR'||!$key||!$salt||($payload['key']??null)!==$key||($payload['txnid']??null)!==$tx->reference||!$this->amount(['currency_code'=>'INR','value'=>$payload['amount']??null],$tx)||!empty($payload['splitInfo']))throw new RuntimeException('PayU payment does not match the stored order.');
  $reverse=array_merge([$salt,$payload['status']??''],array_fill(0,5,''),array_map(fn($n)=>(string)($payload['udf'.$n]??''),[5,4,3,2,1]),[$payload['email']??'',$payload['firstname']??'',$payload['productinfo']??'',$payload['amount'],$tx->reference,$key]);
  if(isset($payload['additionalCharges']))array_unshift($reverse,(string)$payload['additionalCharges']);
  if(!is_string($payload['hash']??null)||!hash_equals(hash('sha512',implode('|',$reverse)),strtolower($payload['hash'])))throw new RuntimeException('PayU response signature is invalid.');
  $checkout=$tx->provider_payload['checkout']??[];foreach(['email','firstname','productinfo']as$field)if(isset($checkout[$field])&&($payload[$field]??null)!==$checkout[$field])throw new RuntimeException('PayU response differs from checkout.');
  $url=$gateway->environment==='live'?'https://info.payu.in/merchant/postservice.php?form=2':'https://test.payu.in/merchant/postservice?form=2';
  $response=Http::timeout(30)->asForm()->post($url,['key'=>$key,'command'=>'verify_payment','var1'=>$tx->reference,'hash'=>hash('sha512',$key.'|verify_payment|'.$tx->reference.'|'.$salt)]);$response->throw();
  $details=$response->json('transaction_details.'.$tx->reference,[]);
  $paid=(int)$response->json('status')===1&&($details['txnid']??null)===$tx->reference&&($details['status']??null)==='success'&&($details['unmappedstatus']??null)==='captured'&&$this->amount(['currency_code'=>'INR','value'=>$details['transaction_amount']??$details['amt']??null],$tx);
  return ['paid'=>$paid,'reference'=>$tx->provider_reference,'payload'=>['verification'=>$details]];
 }
 private function supported(string $provider):void{if(!in_array($provider,self::SUPPORTED,true))throw new RuntimeException('This gateway is unavailable until its authenticated payment adapter is configured and verified.');}
 private function base(PaymentGatewaySetting $g):string{return $g->environment==='live'?'https://api-m.paypal.com':'https://api-m.sandbox.paypal.com';}
 private function token(PaymentGatewaySetting $g):string{
  $credentials=$g->credentials;if(empty($credentials['client_id'])||empty($credentials['client_secret']))throw new RuntimeException('PayPal credentials are incomplete.');
  $response=Http::timeout(30)->asForm()->withBasicAuth($credentials['client_id'],$credentials['client_secret'])->post($this->base($g).'/v1/oauth2/token',['grant_type'=>'client_credentials']);$response->throw();
  $token=$response->json('access_token');if(!is_string($token)||$token==='')throw new RuntimeException('PayPal authentication failed.');return $token;
 }
}
