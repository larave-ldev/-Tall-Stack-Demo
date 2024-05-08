<div>
    <div class="container mx-auto">
        @if(!empty($defaultUnDecoratedData))
            <table class="w-full bg-white border border-gray-300 ">
                <caption class="caption-top bg-black/50 font-bold text-white py-2">
                    {{$defaultUnDecoratedData['description']}}
                </caption>
                <thead>
                </thead>
                <tbody>
                <tr class="text-gray-700">
                    @foreach($defaultUnDecoratedData['prices'] as $price)
                        <td class="py-2 px-4 border">{{$price->qty}}</td>
                    @endforeach
                    <td class="py-2 px-4 border">Lead</td>
                </tr>
                <tr class="text-gray-700">
                    @foreach($defaultUnDecoratedData['prices'] as $price)
                        <td class="py-2 px-4 border text-wrap">${{$price->price}}</td>
                    @endforeach
                    <td class="py-2 px-4 border">{{$defaultUnDecoratedData['lead']}}</td>

                </tr>
                </tbody>
            </table>
        @else
            <p class="text-center font-weight-bold" style="background-color: yellow;">
                Oops! It looks like there's no undecorated option available for this product, or pricing information is
                currently unavailable.
            </p>
        @endif
        <table class="w-full bg-white border border-gray-300 my-16 ">
            <caption class="caption-top bg-black/50 font-bold text-white py-2">
                Options
            </caption>
            <thead>
            <tr>
                <th></th>
                <th>Buy</th>
                <th>Setup</th>
                <th>Lead</th>
                <th>Per Unit</th>
            </tr>
            </thead>

            <tbody>
            @if(!empty($this->productVariant))
                @foreach($this->productVariant as $variant)
                    <tr class="text-gray-700">
                        <td class="py-2 px-4 border">{{$variant['description']}}</td>
                        <td class="py-2 px-4 border"> +${{$variant['price']}}</td>
                        <td class="py-2 px-4 border">${{$variant['setup']}}</td>
                        <td class="py-2 px-4 border" title="{{$variant['lead_desc']}}">
                            @if($variant['lead'] != '')
                                {{$variant['lead']}}
                                <span class="ml-1 inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                     class="w-4 h-4 inline-block align-text-bottom">
                                    <path fill-rule="evenodd"
                                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-2a6 6 0 100-12 6 6 0 000 12zm-1-7a1 1 0 112 0v4a1 1 0 11-2 0V9zm1-5a1 1 0 110 2 1 1 0 010-2z"
                                          clip-rule="evenodd"/>
                                </svg>
                            </span>
                            @endif
                        </td>

                        <td class="py-2 px-4 border w-32 text-center">
                            <input type="number" class="border border-gray-300 px-2 py-1 rounded-md w-full"
                                   value="0" max="1" min="0" title="Max Qty 1"
                                   oninput="this.value = Math.abs(this.value)"
                                   wire:change="changeOption($event.target.value,{{$variant['price']}},{{$variant['setup']}},{{$variant['id']}},'{{$variant['description']}}')">
                        </td>
                    </tr>
                @endforeach
            @endif

            </tbody>
        </table>

        <table class="w-full bg-white border border-gray-300 ">
            <caption class="caption-top bg-black/50 font-bold text-white py-2">
                Buy
            </caption>
            <thead>
            </thead>
            <tbody>
            <tr class="text-gray-700">
                <td class="py-2 px-4 border b">Qty</td>
                @if(!empty($buyTableData))
                    @foreach($buyTableData as $buy)
                        <td class="py-2 px-4 border w-32">
                            {{$buy['qty']}}
                        </td>
                    @endforeach
                @endif
            </tr>
            <tr class="text-gray-700">
                <td class="py-2 px-4 border">Unit Price</td>
                @if(!empty($buyTableData))
                    @foreach($buyTableData as $buy)
                        <td class="py-2 px-4 border">
                            ${{number_format($buy['price'],2)}}
                        </td>
                    @endforeach
                @endif
            </tr>
            <tr class="text-gray-700">
                <td class="py-2 px-4 border">Setup</td>
                @if(!empty($buyTableData))
                    @foreach($buyTableData as $buy)
                        @if($buy['setup'])
                            <td class="py-2 px-4 border">
                                ${{number_format($buy['setup'],2)}}
                            </td>
                        @else
                            <td class="py-2 px-4 border">$0</td>
                        @endif
                    @endforeach
                @endif

            </tr>
            <tr class="text-gray-700">
                <td class="py-2 px-4 border bg-gray-300">Total</td>
                @if(!empty($buyTableData))
                    @foreach($buyTableData as $buy)
                        <td class="py-2 px-4 border bg-gray-300">
                            ${{number_format((($buy['price']*$buy['qty'])+$buy['setup']),2)}}</td>
                    @endforeach
                @endif
            </tr>
            </tbody>
        </table>
        <div class="my-4 py-2 border" style="border-color: #FF0000 ; background-color: #FFE4E4;">
            <h6 class="  text-danger-600 px-2">READ FIRST</h6>
            <ol class="list-decimal pl-6" style="list-style-type: decimal;
                     margin-left: 40px;">
                <li class="mb-2 text-danger-600 ">Never quote the above prices to customers, they are our buy prices
                    only.
                </li>
                <li class="mb-2 text-danger-600 ">Use markup calculator below, but ask a senior to confirm markups
                    before proceeding (They vary per supplier).
                </li>
                <li class="mb-2 text-danger-600 ">Be mindful of setup fees and delivery costs.</li>
                <li class="text-danger-600">All prices are EXCLUDING GST.</li>
            </ol>
        </div>

        <table class="w-full bg-white border border-gray-300 ">
            <caption class="caption-top bg-black/50 font-bold text-white py-2">
                Markup Percentage
            </caption>
            <thead>
            </thead>
            <tbody>
            @if(!empty($unDecoratedData))
                <tr class="text-gray-700">
                    <td class="py-2 px-4 border"></td>
                    @foreach($unDecoratedData['prices'] as $price)
                        <td class="py-2 px-4 border">{{$price->qty}}</td>
                    @endforeach
                </tr>
            @endif
            <tr class="text-gray-700">
                <td class="py-2 px-4 border">Markup</td>
                @if(!empty($unDecoratedData))
                    @foreach($unDecoratedData['markup'] as $markupValue)
                        <td class="py-2 px-4 border w-32 text-center">
                            <input type="number" class="border border-gray-300 px-2 py-1 rounded-md w-full"
                                   min="0"
                                   oninput="this.value = Math.abs(this.value)"
                                   value="{{$markupValue['markup']}}"
                                   wire:change="changeMarkup({{$markupValue['qty']}},$event.target.value)">
                        </td>
                    @endforeach
                @endif
            </tr>
            <tr class="text-gray-700">
                <td class="py-2 px-4 border">Setup</td>
                @if(!empty($unDecoratedData))
                    <td class="py-2 px-4 border w-32" colspan="5">
                        <input type="number" class="border border-gray-300 px-2 py-1 rounded-md w-20"
                               value="{{$defaultSetup}}"
                               oninput="this.value = Math.abs(this.value)"
                               min=" 0"
                               wire:change="changeSetupValue($event.target.value)">
                    </td>
                @endif
            </tr>
            </tbody>
        </table>

        <div class="my-2">

            <p>When Setup is included in the Unit Price. The Markup applied to the Setup portion of
                the unit price is still as per the "Setup" percentage and not the general "Markup"
                percentage. </p>
        </div>

        <table class="w-full bg-white border border-gray-300 my-4 ">
            <caption class="caption-top bg-black/50 font-bold text-white py-2">
                Sell
            </caption>
            <thead>
            </thead>
            <tbody>
            <tr class="text-gray-700">
                <td class="py-2 px-4 border b">Description</td>
                <td class="py-2 px-4 border w-32 " colspan="5">
                    @if(!empty($unDecoratedData))
                        <p>{{$unDecoratedData['description']}}</p>
                    @endif
                    @if(!empty($calculateBuyData))
                        @foreach($calculateBuyData as $data)
                            <p>{{$data['description']}}</p>
                        @endforeach
                    @endif
                </td>
            </tr>
            @if(!empty($sellTableData))
                <tr class="text-gray-700">
                    <td class="py-2 px-4 border">Qty</td>
                    @foreach($sellTableData as $sell)
                        <td class="py-2 px-4 border">{{$sell['qty']}}</td>
                    @endforeach
                </tr>
                <tr class="text-gray-700">
                    <td class="py-2 px-4 border">Unit</td>
                    @foreach($sellTableData as $sell)
                        <td class="py-2 px-4 border">${{number_format($sell['price'],2)}}</td>
                    @endforeach

                </tr>


                <tr class="text-gray-700">
                    <td class="py-2 px-4 border">Setup</td>
                    @foreach($sellTableData as $sell)
                        @if(isset($sell['setup'])&&$sell['setup'])
                            <td class="py-2 px-4 border">
                                ${{number_format($sell['setup'],2)}}
                            </td>
                        @else
                            <td class="py-2 px-4 border">$0</td>
                        @endif
                    @endforeach

                </tr>
                <tr class="text-gray-700">
                    <td class="py-2 px-4 border bg-gray-300">Total</td>
                    @foreach($sellTableData as $sell)
                        <td class="py-2 px-4 border bg-gray-300">
                            ${{number_format( (($sell['price']*$sell['qty']) + $sell['setup']),2)}}
                        </td>
                    @endforeach
                </tr>
            @endif
            </tbody>
        </table>
        @if(!empty($unDecoratedData))
            <button
                class="bg-black/50 p-2 px-6 font-bold text-white  rounded"
                x-on:click="$dispatch('open-modal', {id:'custom-modal-handle'})"
            >

                View Quote

            </button>

        @endif

        <x-filament::modal
            id='custom-modal-handle' width="2xl" class="quote-details">
            <x-slot name="heading">
                @if(!empty($productDetails && $productDetails['sku']))
                    <span class="">
                        {{$productDetails['name']}}
                    </span>
                @endif
            </x-slot>

            <x-slot name="description">
                @if(!empty($calculateBuyData))
                    @foreach($calculateBuyData as $key => $data)
                        <span class="text-xs font-bold">{{$data['description']}}{{ $loop->last ? '' : ' |' }}</span>
                    @endforeach
                @endif

            </x-slot>

            @if(!empty($productDetails && $productDetails['hero_image']))
                <img style="max-width:150px; max-h-height:330px;"
                     src="{{asset($productDetails['hero_image'])}}"/>
            @endif

            <div class="text-xs">
                @if(isset($sellTableData))
                    @foreach($sellTableData as $sell)
                        <p class="mb-2">
                            {{  $sell['qty'] ." "."Units = $". number_format($sell['price'],2) ." Each. $". number_format($sell['setup'],2)." Setup + Delivery + GST" }}
                            {{--                            $". number_format($sell['delivery'],2)."--}}
                            <br/>
                            {{ "Grand Total = $".$sell['grand_total_price']." (Including GST)"}}

                            <br/>

                        </p>
                    @endforeach
                @endif
            </div>
            <x-slot name="footer">
                <div class="modal-footer" data-html2canvas-ignore="true">
                    <div class="flex justify-center gap-x-2">
                        <form wire:submit.prevent="emailProductQuotation" class="flex justify-center gap-x-2 w-full">
                            <input type="text" wire:model="email" name="email" id="email"
                                   class="w-full text-sm h-7 p-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-300 shadow-sm"/>
                            <button type="submit"
                                    class="bg-black/50 h-7 p-1 px-4 text-sm font-bold text-white  rounded ">
                                Email
                            </button>
                        </form>

                        <button class="bg-black/50 h-7 p-1 px-4 text-sm font-bold text-white  rounded" id="copyButton">
                            Copy
                        </button>
                        <button class="bg-black/50 h-7 p-1 px-4 text-sm font-bold text-white  rounded"
                                id="downloadButton">
                            Download
                        </button>

                        <button class="bg-black/50 h-7 p-1 px-4 text-sm font-bold text-white  rounded"
                                x-on:click="$dispatch('close-modal', {id:'custom-modal-handle'})">
                            Close
                        </button>

                    </div>
                    @error('email') <span class="text-danger-600">{{ $message }}</span> @enderror
                </div>

            </x-slot>
        </x-filament::modal>
    </div>
</div>
@livewireScripts
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.min.js"></script>
<script src="{{ asset('js/copy-to-clipboard.js') }}"></script>
