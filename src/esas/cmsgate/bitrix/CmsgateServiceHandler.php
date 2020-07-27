<?
namespace esas\cmsgate\bitrix;

use Bitrix\Main\Error;
use Bitrix\Main\Request;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem\Service;
use Bitrix\Sale\PriceMaths;
use esas\cmsgate\Registry;
use esas\cmsgate\utils\Logger;
use Exception;
use Throwable;

Loc::loadMessages(__FILE__);

abstract class CmsgateServiceHandler extends PaySystem\ServiceHandler
{
    /**
     * @var Logger
     */
    protected $logger;

    public function __construct($type, Service $service)
    {
        parent::__construct($type, $service);
        $this->logger = Logger::getLogger(get_class($this));
    }


    /**
     * Идентифицирует колбэк от платежной системы. Адрес возврата:
     * http://some.domain/bitrix/tools/sale_ps_result.php?handler=hutkigrosh
	 * @return array
	 */
	static public function getIndicativeFields()
	{
		return array('handler' => Registry::getRegistry()->getModuleDescriptor()->getModuleMachineName());
	}


    public function getPaymentIdFromRequest(Request $request)
    {
        try {
            return $this->getPaymentIdFromRequestSafe($request);
        } catch (Exception $e) {
            $this->logger->error("GetPaymentId Exception: " . $e->getMessage(), $e);
        } catch (Throwable $e) {
            $this->logger->error("GetPaymentId Exception: " . $e->getMessage(), $e);
        }
    }

    public abstract function getPaymentIdFromRequestSafe(Request $request);


    public function processRequest(Payment $payment, Request $request)
    {
        try {
            return $this->processRequestSafe($payment, $request);
        } catch (Throwable $e) {
            $this->logger->error("Exception:", $e);
            $result = new PaySystem\ServiceResult();
            $result->addError(new Error($e->getMessage()));
        }
        return $result;
    }

    public abstract function processRequestSafe(Payment $payment, Request $request);

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return bool
	 */
	private function isCorrectSum($paySum, $pgSum, $currency)
	{
		return PriceMaths::roundByFormatCurrency($paySum, $currency) == PriceMaths::roundByFormatCurrency($pgSum, $currency);
	}


	public function sendResponse(PaySystem\ServiceResult $result, Request $request)
	{
		if($result->isSuccess()){
			$data = $result->getData();
			echo '<p>'.(empty($data['orderStatusDesc'])?Loc::getMessage('SALE_HPS_PAYMENTGATE_STATUS_OK'):$data['orderStatusDesc']).'</p>';
		}else{
			echo '<p class="error">'.implode('<br/>',$result->getErrorMessages()).'</p>';
		}
	}

	/**
	 * @return array
	 */
	public function getCurrencyList()
	{
		return array('RUB', 'USD', 'EUR', 'BYN');
	}

	/**
	 * @param Payment $payment
	 * @return mixed
	 */
	protected function isTestMode(Payment $payment = null)
	{
		return Registry::getRegistry()->getConfigWrapper()->isSandbox();
	}
}