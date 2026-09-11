<?php
namespace App\Services\Payments;
use App\Models\{PaymentGatewaySetting,PaymentTransaction};
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GatewayManager {
 public function enabled(): array { return PaymentGatewaySetting::where('enabled',true)->orderBy('label')->get()->all(); }
 public function begin(PaymentTransaction $tx, PaymentGatewaySetting $g): array {
  return match($g->provider){
   'paypal'=>$this->paypalBegin($tx,$g),
   'payu'=>$this->payuBegin($tx,$g),
   'phonepe'=>$this->phonePeBegin($tx,$g),
   'paytm'=>$this->paytmBegin($tx,$g),
   default=>throw new \RuntimeException('Unsupported payment gateway.')
  };
 }
 public function verify(string $provider,array $payload,PaymentGatewaySetting $g): array {
  return match($provider){
   'paypal'=>$this->paypalVerify($payload,$g),
   'payu'=>$this->payuVerify($payload,$g),
   'phonepe'=>$this->phonePeVerify($payload,$g),
   'paytm'=>$this->paytmVerify($payload,$g),
   default=>['paid'=>false,'reference'=>null,'payload'=>$payload]
  };
 }
 private function paypalBase(PaymentGatewaySetting $g): string { return $g->environment==='live'?'https://api-m.paypal.com':'https://api-m.sandbox.paypal.com'; }
 private function paypalToken(PaymentGatewaySetting $g): string {
  $c=$g->credentials??[];$r=Http::asForm()->withBasicAuth($c['client_id']??'',$c['client_secret']??'')->post($this->paypalBase($g).'/v1/oauth2/token',['grant_type'=>'client_credentials']);$r->throw();return (string)$r->json('access_token');
 }
 private function paypalBegin(PaymentTransaction $tx,PaymentGatewaySetting $g): array {
  $token=$this->paypalToken($g);$r=Http::withToken($token)->post($this->paypalBase($g).'/v2/checkout/orders',['intent'=>'CAPTURE','purchase_units'=>[['reference_id'=>$tx->reference,'amount'=>['currency_code'=>$tx->currency,'value'=>(string)$tx->amount]]],'application_context'=>['return_url'=>route('payments.callback',['provider'=>'paypal','tx'=>$tx->reference]),'cancel_url'=>route('payments.cancel',['tx'=>$tx->reference])]]);$r->throw();$url=collect($r->json('links',[]))->firstWhere('rel','approve')['href']??null;return ['type'=>'redirect','url'=>$url,'provider_reference'=>$r->json('id'),'payload'=>$r->json()];
 }
 private function paypalVerify(array $p,PaymentGatewaySetting $g): array {
  $order=$p['token']??null;if(!$order)return ['paid'=>false,'reference'=>null,'payload'=>$p];$token=$this->paypalToken($g);$r=Http::withToken($token)->withHeaders(['Content-Type'=>'application/json'])->post($this->paypalBase($g).'/v2/checkout/orders/'.$order.'/capture');$r->throw();return ['paid'=>$r->json('status')==='COMPLETED','reference'=>$order,'payload'=>$r->json()];
 }
 private function payuBegin(PaymentTransaction $tx,PaymentGatewaySetting $g): array {
  $c=$g->credentials??[];$key=$c['merchant_key']??'';$salt=$c['salt']??'';$product=$tx->plan->name;$first=$tx->user->name;$email=$tx->user->email;$hash=hash('sha512',implode('|',[$key,$tx->reference,$tx->amount,$product,$first,$email,'','','','','','','','','','',$salt]));$endpoint=$g->environment==='live'?'https://secure.payu.in/_payment':'https://test.payu.in/_payment';return ['type'=>'form','url'=>$endpoint,'fields'=>['key'=>$key,'txnid'=>$tx->reference,'amount'=>$tx->amount,'productinfo'=>$product,'firstname'=>$first,'email'=>$email,'phone'=>$tx->user->mobile,'surl'=>route('payments.callback',['provider'=>'payu','tx'=>$tx->reference]),'furl'=>route('payments.callback',['provider'=>'payu','tx'=>$tx->reference]),'hash'=>$hash],'payload'=>[]];
 }
 private function payuVerify(array $p,PaymentGatewaySetting $g): array { return ['paid'=>($p['status']??'')==='success','reference'=>$p['mihpayid']??($p['txnid']??null),'payload'=>$p]; }
 private function phonePeBegin(PaymentTransaction $tx,PaymentGatewaySetting $g): array {
  $c=$g->credentials??[]; if(empty($c['checkout_url'])) throw new \RuntimeException('PhonePe checkout URL is not configured. Add the current PhonePe PG checkout endpoint in Admin > Payment Gateways.');
  // API versions change frequently. Admin supplies the current endpoint and bearer token/client configuration from PhonePe PG.
  $payload=['merchantOrderId'=>$tx->reference,'amount'=>(int)round(((float)$tx->amount)*100),'redirectUrl'=>route('payments.callback',['provider'=>'phonepe','tx'=>$tx->reference]),'metaInfo'=>['udf1'=>$tx->user->email]];
  $headers=[];if(!empty($c['authorization']))$headers['Authorization']=$c['authorization'];$r=Http::withHeaders($headers)->post($c['checkout_url'],$payload);$r->throw();$url=$r->json('redirectUrl')??$r->json('data.instrumentResponse.redirectInfo.url')??null;return ['type'=>'redirect','url'=>$url,'provider_reference'=>$r->json('orderId')??$tx->reference,'payload'=>$r->json()];
 }
 private function phonePeVerify(array $p,PaymentGatewaySetting $g): array { $state=strtoupper((string)($p['state']??$p['code']??$p['status']??''));return ['paid'=>in_array($state,['COMPLETED','PAYMENT_SUCCESS','SUCCESS']), 'reference'=>$p['transactionId']??$p['orderId']??null,'payload'=>$p]; }
 private function paytmBegin(PaymentTransaction $tx,PaymentGatewaySetting $g): array {
  $c=$g->credentials??[]; if(empty($c['checkout_url'])) throw new \RuntimeException('Paytm checkout URL/checksum integration is not configured. Install the current official Paytm checksum package and configure its checkout URL in Admin > Payment Gateways.');
  throw new \RuntimeException('Paytm is configuration-ready but requires Paytm\'s current official checksum utility. See docs/PAYMENTS.md.');
 }
 private function paytmVerify(array $p,PaymentGatewaySetting $g): array { return ['paid'=>in_array(strtoupper((string)($p['STATUS']??'')),['TXN_SUCCESS','SUCCESS']), 'reference'=>$p['TXNID']??null,'payload'=>$p]; }
}
