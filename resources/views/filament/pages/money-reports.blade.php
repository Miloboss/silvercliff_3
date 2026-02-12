<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6">
        <x-filament::section>
            <x-slot name="heading">
                Booking Type Breakdown
            </x-slot>
            
            <table class="w-full text-left divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-2">Type</th>
                        <th class="px-4 py-2">Bookings</th>
                        <th class="px-4 py-2">Total Guests</th>
                        <th class="px-4 py-2 text-right">Revenue (THB)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @php
                        [$from, $until] = $this->getDateRange();
                        $types = ['room', 'tour', 'package'];
                        $grandTotal = 0;
                        $grandBookings = 0;
                        $grandGuests = 0;
                    @endphp

                    @foreach($types as $type)
                        @php
                            $query = \App\Models\Booking::where('booking_type', $type)
                                ->whereIn('status', ['pending', 'confirmed'])
                                ->where(function ($q) use ($from, $until, $type) {
                                    if ($type === 'room') {
                                        $q->whereHas('roomDetail', fn($sq) => $sq->where('check_in', '<=', $until)->where('check_out', '>', $from));
                                    } elseif ($type === 'tour') {
                                        $q->whereHas('tourDetail', fn($sq) => $sq->whereBetween('tour_date', [$from, $until]));
                                    } elseif ($type === 'package') {
                                        $q->whereHas('packageDetail', fn($sq) => $sq->where('check_in', '<=', $until)->where('check_out', '>', $from));
                                    }
                                });

                            $count = $query->count();
                            $revenue = $query->sum('total_amount');
                            
                            $guests = 0;
                            $bookings = $query->get();
                            foreach($bookings as $b) {
                                if($type === 'room') $guests += ($b->roomDetail->guests_adults ?? 0) + ($b->roomDetail->guests_children ?? 0);
                                if($type === 'tour') $guests += ($b->tourDetail->guests_adults ?? 0) + ($b->tourDetail->guests_children ?? 0);
                                if($type === 'package') $guests += ($b->packageDetail->guests_adults ?? 0) + ($b->packageDetail->guests_children ?? 0);
                            }

                            $grandTotal += $revenue;
                            $grandBookings += $count;
                            $grandGuests += $guests;
                        @endphp
                        <tr>
                            <td class="px-4 py-2 capitalize font-medium">{{ $type }}</td>
                            <td class="px-4 py-2">{{ $count }}</td>
                            <td class="px-4 py-2">{{ $guests }}</td>
                            <td class="px-4 py-2 text-right font-bold">{{ number_format($revenue, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 dark:bg-gray-800">
                        <td class="px-4 py-2 font-bold">GRAND TOTAL</td>
                        <td class="px-4 py-2 font-bold">{{ $grandBookings }}</td>
                        <td class="px-4 py-2 font-bold">{{ $grandGuests }}</td>
                        <td class="px-4 py-2 text-right font-bold text-lg text-primary-600 dark:text-primary-400">
                            {{ number_format($grandTotal, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </x-filament::section>
    </div>
</x-filament-panels::page>
