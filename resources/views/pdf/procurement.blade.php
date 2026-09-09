<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pengadaan #{{ $procurement->id }}</title>

    <style>
        @page {
            margin: 12mm 12mm 14mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5px;
            color: #1b1b18;
            margin: 0;
            padding: 0;
            line-height: 1.45;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        /* ============================================================
           LETTERHEAD
        ============================================================ */

        .letterhead-wrapper {
            width: 100%;
            text-align: center;
            margin: 0 0 5px;
            padding: 0;
        }

        .letterhead-image {
            display: block;
            width: 100%;
            max-width: 100%;
            height: auto;
            max-height: 48mm;
            margin: 0 auto;
        }

        .letterhead-line {
            display: none;
        }

        /* ============================================================
           TITLE
        ============================================================ */

        .doc-title {
            text-align: center;
            margin: 6px 0 14px;
        }

        .doc-title h1 {
            font-size: 19px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin: 0 0 5px;
            font-weight: bold;
            color: #1b1b18;
        }

        .doc-title .doc-number {
            font-size: 9.5px;
            color: #555550;
            margin: 0 0 7px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 11px;
            border: 1px solid #d6d3d1;
            background: #f5f5f4;
            color: #57534e;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-approved {
            background: #f0fdf4;
            color: #166534;
            border-color: #bbf7d0;
        }

        .status-rejected {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .status-completed {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }

        .status-pending {
            background: #f5f5f4;
            color: #57534e;
            border-color: #d6d3d1;
        }

        /* ============================================================
           SECTION
        ============================================================ */

        .section {
            margin-top: 13px;
        }

        /* ============================================================
           SECTION HEADING
           
           Satu cell agar DomPDF tidak memberikan jarak antara
           nomor section dan judul.

           Tanpa background.
           Tanpa underline.
           Tanpa border.
           
           Format:
           A. INFORMASI PENGADAAN
           B. DETAIL BARANG
           C. ALASAN PENGADAAN
           D. VERIFIKASI
           E. TANDA TANGAN
        ============================================================ */

        .section-heading {
            width: 100%;
            margin: 0 0 7px;
            padding: 0;
            border: none;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .section-heading td {
            width: 100%;
            margin: 0;
            padding: 0 !important;
            border: none !important;
            vertical-align: middle;
        }

        .section-heading-text {
            width: 100%;
            margin: 0;
            padding: 0;

            font-size: 10.5px;
            line-height: 16px;

            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.45px;

            color: #1b1b18;

            white-space: normal;
        }

        /* ============================================================
           DETAIL TABLE - A / D
        ============================================================ */

        .detail-table {
            width: 100%;
            border: 1px solid #d9d9d5;
            border-radius: 4px;
            overflow: hidden;
        }

        .detail-table td {
            border-bottom: 1px solid #e5e5e1;
            padding: 6px 9px;
            font-size: 9px;
            vertical-align: middle;
        }

        .detail-table tr:last-child td {
            border-bottom: none;
        }

        .detail-table .label {
            background: #f7f7f5;
            font-weight: bold;
            color: #555550;
            font-size: 8.5px;
            width: 155px;
        }

        /* ============================================================
           ITEM TABLE - B
        ============================================================ */

        .item-table {
            width: 100%;
            border: 1px solid #d0d0cb;
        }

        .item-table th {
            background: #f1f1ee;
            border: 1px solid #cfcfca;
            padding: 6px 7px;
            font-size: 7.8px;
            text-transform: uppercase;
            text-align: left;
            font-weight: bold;
            color: #333330;
        }

        .item-table td {
            border: 1px solid #d8d8d3;
            padding: 7px;
            font-size: 8.8px;
            vertical-align: top;
        }

        .item-table td.center {
            text-align: center;
            vertical-align: middle;
        }

        .item-room-location {
            font-size: 7.8px;
            color: #66635e;
            margin-top: 2px;
        }

        /* ============================================================
           REASON / NOTE / STATEMENT - C
        ============================================================ */

        .reason-box,
        .note-box,
        .statement-box {
            border: 1px solid #d9d9d5;
            border-radius: 4px;
            background: #fafaf9;
            padding: 9px 11px;
            font-size: 9px;
            line-height: 1.55;
        }

        .reason-box {
            border-left: 3px solid #991b1b;
        }

        .note-box {
            background: #fafaf9;
        }

        .note-label {
            font-size: 7.8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #555550;
            margin-bottom: 3px;
            display: block;
        }

        .statement-box {
            text-align: justify;
            background: #f7f7f5;
            font-size: 8.8px;
            line-height: 1.6;
        }

        /* ============================================================
           SIGNATURE - E
        ============================================================ */

        .signature-table {
            width: 100%;
            table-layout: fixed;
        }

        .signature-table td {
            width: 33.33%;
            border: 1px solid #d9d9d5;
            padding: 9px 8px;
            text-align: center;
            vertical-align: top;
        }

        .signature-role {
            font-size: 8.5px;
            font-weight: bold;
            color: #333330;
        }

        .signature-subrole {
            font-size: 7.5px;
            color: #66635e;
            margin-top: 2px;
            min-height: 11px;
        }

        .signature-space {
            height: 82px;
            text-align: center;
            vertical-align: middle;
            padding: 5px 0;
        }

        .signature-space img {
            display: inline-block;
            max-height: 75px;
            max-width: 96%;
            width: auto;
            height: auto;
        }

        .signature-placeholder {
            font-size: 7.2px;
            color: #999;
            font-style: italic;
        }

        .signature-name {
            font-size: 8.7px;
            font-weight: bold;
            color: #1b1b18;
            margin-top: 2px;
        }

        .signature-position {
            font-size: 7.8px;
            color: #555550;
            margin-top: 2px;
        }

        .signature-nip {
            font-size: 7.3px;
            color: #66635e;
            margin-top: 2px;
        }

        /* ============================================================
           FOOTER
        ============================================================ */

        .footer-table {
            margin-top: 14px;
            border-top: 1px solid #d6d6d2;
            padding-top: 6px;
        }

        .footer-table td {
            font-size: 7px;
            color: #666;
            padding: 0;
        }

        /* ============================================================
           PDF PAGE BREAK
        ============================================================ */

        .section,
        .detail-table,
        .item-table,
        .reason-box,
        .note-box,
        .statement-box,
        .signature-table {
            page-break-inside: avoid;
        }

        .signature-table tr {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    {{-- ================================================================
         LETTERHEAD
    ================================================================= --}}

    @if($letterheadPath)
        <div class="letterhead-wrapper">
            <img
                src="{{ $letterheadPath }}"
                class="letterhead-image"
                alt="Kop Surat"
            >
        </div>
    @endif


    {{-- ================================================================
         TITLE
    ================================================================= --}}

    <div class="doc-title">

        <h1>
            Pengadaan Barang
        </h1>

        <p class="doc-number">
            Nomor Dokumen:
            <strong>
                {{ $procurement->document_number ?: 'PROC/'.$procurement->id }}
            </strong>
        </p>

        <span class="status-badge status-{{ $procurement->status }}">
            {{ $statusLabel }}
        </span>

    </div>


    {{-- ================================================================
         A. INFORMASI PENGADAAN
    ================================================================= --}}

    <div class="section">

        <table class="section-heading">
            <tr>
                <td class="section-heading-text">
                    A. INFORMASI PENGADAAN
                </td>
            </tr>
        </table>

        <table class="detail-table">

            <tr>
                <td class="label">
                    Fakultas
                </td>

                <td>
                    {{ $faculty->name ?? '-' }}
                </td>
            </tr>

            <tr>
                <td class="label">
                    Pemohon
                </td>

                <td>
                    {{ $requester->name ?? '-' }}
                </td>
            </tr>

            <tr>
                <td class="label">
                    Jabatan Pemohon
                </td>

                <td>
                    {{ $requester->position ?? 'Pemohon' }}
                </td>
            </tr>

            @if($requester->nip ?? false)
                <tr>
                    <td class="label">
                        NIP Pemohon
                    </td>

                    <td>
                        {{ $requester->nip }}
                    </td>
                </tr>
            @endif

            <tr>
                <td class="label">
                    Tanggal Pengajuan
                </td>

                <td>
                    {{ $requestedAt }}
                </td>
            </tr>

            @if($processedAt)
                <tr>
                    <td class="label">
                        Tanggal Verifikasi
                    </td>

                    <td>
                        {{ $processedAt }}
                    </td>
                </tr>
            @endif

        </table>

    </div>


    {{-- ================================================================
         B. DETAIL BARANG
    ================================================================= --}}

    <div class="section">

        <table class="section-heading">
            <tr>
                <td class="section-heading-text">
                    B. DETAIL BARANG
                </td>
            </tr>
        </table>

        <table class="item-table">

            <thead>

                <tr>

                    <th style="width: 30px; text-align: center;">
                        No.
                    </th>

                    <th>
                        Nama Barang
                    </th>

                    <th style="width: 55px; text-align: center;">
                        Jumlah
                    </th>

                    <th style="width: 105px;">
                        Jenis
                    </th>

                    <th>
                        Ruangan
                    </th>

                </tr>

            </thead>

            <tbody>

                <tr>

                    <td class="center">
                        1.
                    </td>

                    <td>
                        <strong>
                            {{ $procurement->item_name }}
                        </strong>
                    </td>

                    <td class="center">
                        {{ $procurement->quantity }}
                    </td>

                    <td>
                        {{ $typeLabel }}
                    </td>

                    <td>

                        {{ $roomLabel }}

                        @if($roomLocation)
                            <div class="item-room-location">
                                {{ $roomLocation }}
                            </div>
                        @endif

                    </td>

                </tr>

            </tbody>

        </table>

    </div>


    {{-- ================================================================
         C. ALASAN PENGADAAN
    ================================================================= --}}

    <div class="section">

        <table class="section-heading">
            <tr>
                <td class="section-heading-text">
                    C. ALASAN PENGADAAN
                </td>
            </tr>
        </table>

        <div class="reason-box">
            {{ $procurement->reason ?: '-' }}
        </div>

    </div>


    {{-- ================================================================
         D. VERIFIKASI
    ================================================================= --}}

    <div class="section">

        <table class="section-heading">
            <tr>
                <td class="section-heading-text">
                    D. VERIFIKASI
                </td>
            </tr>
        </table>

        <table class="detail-table">

            <tr>

                <td class="label">
                    Status
                </td>

                <td>

                    <span class="status-badge status-{{ $procurement->status }}">
                        {{ $statusLabel }}
                    </span>

                </td>

            </tr>

            @if($processor)

                <tr>

                    <td class="label">
                        Verifikator
                    </td>

                    <td>
                        {{ $processor->name }}
                    </td>

                </tr>

                <tr>

                    <td class="label">
                        Jabatan Verifikator
                    </td>

                    <td>
                        {{ $processor->position ?? 'Verifikator' }}
                    </td>

                </tr>

                @if($processor->nip ?? false)

                    <tr>

                        <td class="label">
                            NIP Verifikator
                        </td>

                        <td>
                            {{ $processor->nip }}
                        </td>

                    </tr>

                @endif

            @endif

            @if($processedAt)

                <tr>

                    <td class="label">
                        Waktu Verifikasi
                    </td>

                    <td>
                        {{ $processedAt }}
                    </td>

                </tr>

            @endif

        </table>


        @if($procurement->admin_note)

            <div class="note-box" style="margin-top: 8px;">

                <span class="note-label">
                    Catatan Verifikasi
                </span>

                {{ $procurement->admin_note }}

            </div>

        @endif

    </div>


    {{-- ================================================================
         PERNYATAAN
    ================================================================= --}}

    <div class="section">

        <div class="statement-box">

            Dengan ini pengajuan pengadaan barang tersebut telah dibuat
            berdasarkan kebutuhan yang sebenarnya dan dapat
            dipertanggungjawabkan sesuai dengan ketentuan yang berlaku.

        </div>

    </div>


    {{-- ================================================================
         E. TANDA TANGAN
    ================================================================= --}}

    <div class="section">

        <table class="section-heading">
            <tr>
                <td class="section-heading-text">
                    E. TANDA TANGAN
                </td>
            </tr>
        </table>

        <table class="signature-table">

            <tr>

                {{-- ====================================================
                     PENGAJU
                ===================================================== --}}

                <td>

                    <div class="signature-role">
                        {{ $requester->position ?? 'Pemohon' }}
                    </div>

                    <div class="signature-space">

                        @if($requesterSignature)

                            <img
                                src="{{ $requesterSignature }}"
                                alt="Tanda Tangan Pengaju"
                            >

                        @else

                            <span class="signature-placeholder">
                                Tanda tangan belum tersedia
                            </span>

                        @endif

                    </div>

                    <div class="signature-name">
                        {{ $requester->name ?? '-' }}
                    </div>

                    @if($requester->nip ?? false)

                        <div class="signature-nip">
                            NIP. {{ $requester->nip }}
                        </div>

                    @endif

                </td>


                {{-- ====================================================
                     VERIFIKATOR
                ===================================================== --}}

                <td>

                    <div class="signature-role">
                        {{ $processor->position ?? 'Verifikator' }}
                    </div>

                    <div class="signature-space">

                        @if($processorSignature)

                            <img
                                src="{{ $processorSignature }}"
                                alt="Tanda Tangan Verifikator"
                            >

                        @else

                            <span class="signature-placeholder">
                                Tanda tangan belum tersedia
                            </span>

                        @endif

                    </div>

                    <div class="signature-name">
                        {{ $processor->name ?? '-' }}
                    </div>

                    @if($processor->nip ?? false)

                        <div class="signature-nip">
                            NIP. {{ $processor->nip }}
                        </div>

                    @endif

                </td>


                {{-- ====================================================
                     DEKAN
                ===================================================== --}}

                <td>

                    <div class="signature-role">
                        Mengetahui,
                    </div>

                    <div class="signature-subrole">
                        Dekan {{ $faculty->name ?? '-' }}
                    </div>

                    <div class="signature-space">

                        @if($deanSignature)

                            <img
                                src="{{ $deanSignature }}"
                                alt="Tanda Tangan Dekan"
                            >

                        @else

                            <span class="signature-placeholder">
                                Tanda tangan belum tersedia
                            </span>

                        @endif

                    </div>

                    <div class="signature-name">
                        {{ $faculty->dean ?? '-' }}
                    </div>

                    @if($faculty->dean_nip ?? false)

                        <div class="signature-nip">
                            NIP. {{ $faculty->dean_nip }}
                        </div>

                    @endif

                </td>

            </tr>

        </table>

    </div>


    {{-- ================================================================
         FOOTER
    ================================================================= --}}

    <table class="footer-table">

        <tr>

            <td style="text-align: left;">
                Dokumen Pengadaan Barang
            </td>

            <td style="text-align: right;">
                Dicetak: {{ $printedAt }}
            </td>

        </tr>

    </table>

</body>
</html>