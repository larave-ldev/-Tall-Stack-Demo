<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Price Quotation</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>

    <style>
        table, th, td {
            border: 1px solid;
            border-collapse: collapse;
            border-color: lightgray;
        }

        /*p {text-align: justify}*/
    </style>
</head>
<body>
<div>
    <div class="container mx-auto">
        <p>
            Hello,
            <br><br>
            We hope this mail finds you well. We are pleased to provide you with a product quotation as requested.
            Please
            find the details below:

        </p>

        <div>
            @if(!empty($data['productDetails']))
                <h2 style="margin-bottom: 0px;">{{ $data['productDetails']['name'] ?? null }}</h2>
            @endif
            @if(!empty($data['calculateBuyData']))
                @foreach($data['calculateBuyData'] as  $key => $calculateBuyData)
                    <span
                        style="font-size: 0.75rem; line-height: 1rem; ">{{$calculateBuyData['description'] }}{{ $loop->last ? '' : ' |' }}</span>
                @endforeach
            @endif

        </div>


        <div class="w-full" style="width:20%">
            @if(!empty($data['productDetails'] && $data['productDetails']['hero_image']))
                <img src="{{asset($data['productDetails']['hero_image'])}}" style="max-width: 90%;"/>
            @endif
        </div>


        <div class="text-xs" style="">
            @if(isset($data['sellTableData']))
                @foreach($data['sellTableData'] as $sell)
                    <p class="mb-2">
                        {{  $sell['qty'] ." "."Units = $". number_format($sell['price'],2) ." Each. $". number_format($sell['setup'],2)." Setup + Delivery + GST" }}
                        {{--                        $". number_format($sell['delivery'],2)."--}}
                        <br/>
                        {{ "Grand Total = $".$sell['grand_total_price']." (Including GST)"}}
                        <br/>
                    </p>

                @endforeach
            @endif
        </div>
        <!--Footer End-->
        <table width="100%" align="center" cellspacing="0" cellpadding="0"
               style="border: none !important;">
            <tr>
                <td valign="top" align="center"
                    style=" border:none ; color: #000000;font-size: 16px;text-align: center;padding: 15px 15px;">
                    Simple product quotation powered by <a href="{{config('magento.site_url')}}" target="_blank"
                                                           style="text-decoration: none;color: #F59E0B;">promosuperstore.au</a>
                </td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
