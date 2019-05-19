These files can be used to add new services to import curency rates to [Magento2](https://github.com/magento/magento2)

These services are added to [Kozeta_Currency](https://shop.kozeta.lt/m2-any-currency.html) module and also are available here: https://shop.kozeta.lt

<b>Installation:</b>
1) Create new custom module or use any existing one
2) Upload file to directory: `app/code/Vendorname/Modulename/Model/Currency/Import`
3) Add new record to `di.xml`. For example, you add `Frankfurter` service:
```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">

  ....
    <type name="Magento\Directory\Model\Currency\Import\Config">
        <arguments>
            <argument name="servicesConfig" xsi:type="array">
                <item name="frankfurter" xsi:type="array">
                    <item name="label" xsi:type="string">Frankfurter (Fiat)</item>
                    <item name="class" xsi:type="string">Vendorname\Modulename\Model\Currency\Import\Frankfurter</item>
                </item>
            </argument>
        </arguments>
    </type>
 ...
 
</config>
```    
4) Add new record to `system.xml`:
```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Config:etc/system_file.xsd">
    <system>
    
    ...
        <section id="currency">
            <group id="frankfurter" translate="label" sortOrder="10" showInDefault="1" showInWebsite="0" showInStore="0">
                <label>Frankfurter</label>
                <field id="timeout" translate="label" type="text" sortOrder="10" showInDefault="1" showInWebsite="0" showInStore="0">
                    <label>Connection Timeout (sec)</label>
                    <comment>More information is available here https://frankfurter.app/</comment>
                </field>
            </group>
        </section>
      ...
      
    </system>
</config>
```
5) Optionally add a record to `config.xml`
```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Store:etc/config.xsd">
    <default>
    
    ...
        <currency>
            <frankfurter>
                <timeout>100</timeout>
            </frankfurter>
        </currency>
    ...
        
    </default>
</config>
```
6) clear cache and re-compile


Your suggestions are appreciated
