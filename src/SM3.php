<?php
/**
 * Sm3 @ SM3-PHP
 *
 * Code BY ch4o5
 * 10月. 14th 2019
 * Powered by PhpStorm
 */

namespace SM3;

use ArrayAccess;
use Exception;
use SM3\handler\ExtendedCompression;
use SM3\libs\WordConversion;
use SM3\types\BitString;

/**
 * 入口类
 * Class Sm3
 *
 * @package    SM3
 * @error_code 90xxx
 */
class SM3 implements ArrayAccess
{
    /** @var string 初始值常数 */
    const IV = '7380166f4914b2b9172442d7da8a0600a96f30bc163138aae38dee4db0fb0e4e';
    
    /** @var string 消息(加密前的结果) */
    private $message = '';
    /** @var string 杂凑值(加密后的结果) */
    private $hash_value = '';
    
    /**
     * 实例化时直接调用将参数传给主方法
     * Sm3 constructor.
     *
     * @param $message string 传入的消息
     *
     * @throws \Exception
     */
    public function __construct($message)
    {
        // 输入验证
        if (!is_string($message)) throw new Exception('参数类型必须为string，请检查后重新输入', 90001);
        
        /** @var string message 消息 */
        $this->message = $message;
        /** @var string hash_value  杂凑值 */
        $this->hash_value = $this->sm3();
    }
    
    /**
     * 主方法
     *
     * @return string
     */
    private function sm3()
    {
        /** @var string $m 转化后的消息（二进制码） */
        $m = new BitString($this->message);
        
        // 一、填充
        $l = strlen($m);
        
        /** @var int $k 满足l + 1 + k ≡ 448mod512 的最小的非负整数 */
        $k = $l % 512;
        $k = $k + 64 >= 512
            ? 512 - ($k % 448) - 1
            : 512 - 64 - $k - 1;
        
        $bin_l = new BitString($l);
        /** @var BitString $m_fill 填充后的消息 */
        $m_fill = new types\BitString(
            $m # 原始消息m
            . '1' # 拼个1
            . str_pad('', $k, '0') # 拼上k个比特的0
            . (
            strlen($bin_l) >= 64
                ? substr($bin_l, 0, 64)
                : str_pad($bin_l, 64, '0', STR_PAD_LEFT)
            ) # 64比特，l的二进制表示
        );
        
        
        // 二、迭代压缩
        // 迭代过程
        $B = str_split($m_fill, 512);
        /** @var int $n m'可分为的组数 */
        $n = ($l + $k + 65) / 512;
        if (count($B) !== $n) return false;
        
        $V = array(
            WordConversion::hex2bin(self::IV),
        );
        $extended = new ExtendedCompression();
        foreach ($B as $key => $Bi) {
            $V[$key + 1] = $extended->CF($V[$key], $Bi);
        }
    
        // $binary = WordConversion::xorConversion($V);
        $binary = join('', $V);
        assert($binary === '0100010010001011100001001001101111000110111101001101100110010010011000010011010111110101100010001000010000110011111010001100100101010001100101101101000010111110111000010110101110000000000011010110010100101001110001010001010000110110000000110110001110101110');
        $hex = base_convert($binary, 2, 16);
        
        return $hex;
    }
    
    /**
     * 方便直接输出实例化的对象
     *
     * @return string
     * @throws \Exception
     */
    public function __toString()
    {
        return $this->hash_value;
    }
    
    /**
     * Whether a offset exists
     *
     * @link  https://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return bool true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset($this->hash_value[$offset]);
    }
    
    /**
     * Offset to retrieve
     *
     * @link  https://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     *
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->hash_value[$offset];
    }
    
    /**
     * Offset to set
     *
     * @link  https://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     *
     * @return \SM3\SM3
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->hash_value[$offset] = $value;
        return $this;
    }
    
    /**
     * Offset to unset
     *
     * @link  https://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->hash_value[$offset]);
    }
}