<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class RegistroHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('registro');
    }

    public function testExtraerComparacionNumericaConOperador(): void
    {
        $this->assertSame(20.0, registro_extraer_comparacion_numerica('<=20'));
        $this->assertSame(5.5, registro_extraer_comparacion_numerica('> 5,5'));
        $this->assertSame(10.0, registro_extraer_comparacion_numerica('10'));
        $this->assertNull(registro_extraer_comparacion_numerica('NA'));
    }

    public function testInterpretacionReferencialEtiquetaSoloLimiteMaximo(): void
    {
        $result = registro_interpretacion_referencial_etiqueta('213.1', '<=20', '');

        $this->assertIsArray($result);
        $this->assertSame('Alto', $result['label']);
        $this->assertSame('alto', $result['nivel']);
    }

    public function testInterpretacionReferencialEtiquetaSoloLimiteMinimo(): void
    {
        $result = registro_interpretacion_referencial_etiqueta('7', '', '>=10');

        $this->assertIsArray($result);
        $this->assertSame('Bajo', $result['label']);
        $this->assertSame('bajo', $result['nivel']);
    }
}
