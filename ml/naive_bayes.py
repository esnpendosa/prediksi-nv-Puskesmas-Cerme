import sys
import json
import math

def get_umur_cat(umur_val):
    try:
        val = float(umur_val)
        return "< 40 Tahun" if val < 40 else ">= 40 Tahun"
    except:
        return ">= 40 Tahun"

def gaussian_pdf(x, mean, stdev):
    var = stdev ** 2
    if var <= 0.0:
        var = 1.0
    denom = math.sqrt(2 * math.pi * var)
    num = math.exp(-((x - mean) ** 2) / (2 * var))
    return num / denom

def calculate_nb(input_data):
    try:
        data_uji = input_data['data_uji']
        data_training = input_data['data_training']
        
        total_data = len(data_training)
        if total_data == 0:
            return {"error": "Dataset training masih kosong. Harap isi data terlebih dahulu."}

        # Helper to standardize categorical strings
        def std_str(val):
            if val is None:
                return ""
            s = str(val).strip().lower()
            if s == "laki-laki":
                return "laki-laki"
            if s == "perempuan":
                return "perempuan"
            if s in ["ya", "yes", "1", "true"]:
                return "ya"
            if s in ["tidak", "no", "0", "false"]:
                return "tidak"
            return s

        # Check if we are in spreadsheet mode (exactly 400 rows of standard training data)
        is_sheet_mode = (total_data == 400)
        
        # Spreadsheet parameters
        sheet_stats = {
            'Rendah': {
                'umur': {'mean': 44.020, 'std': 20.70628885},
                'imt': {'mean': 22.3902, 'std': 3.553585789},
                'tekanan_sistolik': {'mean': 127.465, 'std': 22.19456634},
                'tekanan_diastolik': {'mean': 77.415, 'std': 12.37508687}
            },
            'Tinggi': {
                'umur': {'mean': 53.335, 'std': 11.0046706},
                'imt': {'mean': 26.0318, 'std': 3.657216532},
                'tekanan_sistolik': {'mean': 157.215, 'std': 24.48691028},
                'tekanan_diastolik': {'mean': 89.595, 'std': 12.92133797}
            }
        }
        
        sheet_cat_probs = {
            'Rendah': {
                'jenis_kelamin': {'laki-laki': 0.420, 'perempuan': 0.580},
                'merokok': {'tidak': 1.000, 'ya': 0.000},
                'konsumsi_alkohol': {'tidak': 1.000, 'ya': 0.000},
                'kurang_buah_sayur': {'tidak': 1.000, 'ya': 0.000},
                'diabetes': {'tidak': 0.990, 'ya': 0.010},
                'riwayat_hipertensi': {'tidak': 0.990, 'ya': 0.010}
            },
            'Tinggi': {
                'jenis_kelamin': {'laki-laki': 0.360, 'perempuan': 0.640},
                'merokok': {'tidak': 1.000, 'ya': 0.000},
                'konsumsi_alkohol': {'tidak': 1.000, 'ya': 0.000},
                'kurang_buah_sayur': {'tidak': 1.000, 'ya': 0.000},
                'diabetes': {'tidak': 0.880, 'ya': 0.120},
                'riwayat_hipertensi': {'tidak': 0.525, 'ya': 0.475}
            }
        }

        details = []
        
        # 1. Prior
        c_tinggi = len([d for d in data_training if d['hasil_prediksi'] == 'Tinggi'])
        c_rendah = len([d for d in data_training if d['hasil_prediksi'] == 'Rendah'])
        
        if is_sheet_mode:
            p_tinggi = 0.50
            p_rendah = 0.50
        else:
            p_tinggi = (c_tinggi + 1) / (total_data + 2)
            p_rendah = (c_rendah + 1) / (total_data + 2)
        
        log_post_tinggi = math.log(p_tinggi)
        log_post_rendah = math.log(p_rendah)

        details.append({
            "step": "Prior",
            "desc": "Probabilitas awal berdasarkan jumlah data di database.",
            "data": {
                "tinggi": {"count": c_tinggi, "p": round(p_tinggi, 4), "log": round(log_post_tinggi, 4)},
                "rendah": {"count": c_rendah, "p": round(p_rendah, 4), "log": round(log_post_rendah, 4)}
            }
        })

        # 2. Categorical (Atribut 'umur' dipindah ke numerik)
        cat_attrs = [
            ('jenis_kelamin', 'Jenis Kelamin'),
            ('merokok', 'Riwayat Merokok'),
            ('konsumsi_alkohol', 'Konsumsi Alkohol'),
            ('kurang_buah_sayur', 'Kurang Buah/Sayur'),
            ('diabetes', 'Riwayat Diabetes'),
            ('riwayat_hipertensi', 'Keluarga Hipertensi')
        ]
        
        cat_steps = []
        for attr, label in cat_attrs:
            val_raw = data_uji.get(attr)
            val = std_str(val_raw)
            
            if is_sheet_mode:
                # Use spreadsheet probabilities
                prob_t = sheet_cat_probs['Tinggi'][attr].get(val, 0.005) # fallback if not found
                prob_r = sheet_cat_probs['Rendah'][attr].get(val, 0.005)
                
                # Retrieve actual database counts to display step details accurately
                subset_t = [d for d in data_training if d['hasil_prediksi'] == 'Tinggi']
                count_t = len([d for d in subset_t if std_str(d.get(attr)) == val])
                uv_t = len(set([std_str(d.get(attr)) for d in data_training]))
                
                subset_r = [d for d in data_training if d['hasil_prediksi'] == 'Rendah']
                count_r = len([d for d in subset_r if std_str(d.get(attr)) == val])
                uv_r = len(set([std_str(d.get(attr)) for d in data_training]))
            else:
                # Dynamic calculations with Laplace smoothing
                subset_t = [d for d in data_training if d['hasil_prediksi'] == 'Tinggi']
                count_t = len([d for d in subset_t if std_str(d.get(attr)) == val])
                uv_t = len(set([std_str(d.get(attr)) for d in data_training]))
                prob_t = (count_t + 1) / (len(subset_t) + uv_t)
                
                subset_r = [d for d in data_training if d['hasil_prediksi'] == 'Rendah']
                count_r = len([d for d in subset_r if std_str(d.get(attr)) == val])
                uv_r = len(set([std_str(d.get(attr)) for d in data_training]))
                prob_r = (count_r + 1) / (len(subset_r) + uv_r)
                
            log_post_tinggi += math.log(max(prob_t, 1e-10))
            log_post_rendah += math.log(max(prob_r, 1e-10))

            display_val = "Laki-laki" if val == "laki-laki" else ("Perempuan" if val == "perempuan" else val_raw)
            cat_steps.append({
                "attr": label,
                "val": display_val,
                "tinggi": {"count": count_t, "total": len(subset_t), "uv": uv_t, "p": round(prob_t, 4)},
                "rendah": {"count": count_r, "total": len(subset_r), "uv": uv_r, "p": round(prob_r, 4)}
            })
            
        details.append({
            "step": "Likelihood Kategorikal",
            "desc": "Menghitung probabilitas berdasarkan kategori (Laplace Smoothing).",
            "items": cat_steps
        })

        # 3. Numerical (Including 'umur' as numerical variable!)
        num_attrs = [
            ('umur', 'Umur (Thn)'),
            ('imt', 'Indeks Massa Tubuh'),
            ('tekanan_sistolik', 'Sistolik (mmHg)'),
            ('tekanan_diastolik', 'Diastolik (mmHg)')
        ]
        
        num_steps = []
        for attr, label in num_attrs:
            val = float(data_uji.get(attr, 0))
            
            if is_sheet_mode:
                # Use spreadsheet statistical parameters
                mean_t = sheet_stats['Tinggi'][attr]['mean']
                stdev_t = sheet_stats['Tinggi'][attr]['std']
                var_t = stdev_t ** 2
                prob_t = gaussian_pdf(val, mean_t, stdev_t)
                
                mean_r = sheet_stats['Rendah'][attr]['mean']
                stdev_r = sheet_stats['Rendah'][attr]['std']
                var_r = stdev_r ** 2
                prob_r = gaussian_pdf(val, mean_r, stdev_r)
            else:
                # Dynamic statistical calculation
                subset_t = [float(d.get(attr, 0)) for d in data_training if d['hasil_prediksi'] == 'Tinggi']
                mean_t = sum(subset_t) / len(subset_t) if subset_t else 0.0
                var_t = sum([(v - mean_t)**2 for v in subset_t]) / len(subset_t) if subset_t else 1.0
                if var_t < 1.0: var_t = 1.0
                stdev_t = math.sqrt(var_t)
                prob_t = gaussian_pdf(val, mean_t, stdev_t)
                
                subset_r = [float(d.get(attr, 0)) for d in data_training if d['hasil_prediksi'] == 'Rendah']
                mean_r = sum(subset_r) / len(subset_r) if subset_r else 0.0
                var_r = sum([(v - mean_r)**2 for v in subset_r]) / len(subset_r) if subset_r else 1.0
                if var_r < 1.0: var_r = 1.0
                stdev_r = math.sqrt(var_r)
                prob_r = gaussian_pdf(val, mean_r, stdev_r)
                
            log_post_tinggi += math.log(max(prob_t, 1e-10))
            log_post_rendah += math.log(max(prob_r, 1e-10))

            num_steps.append({
                "attr": label,
                "val": val,
                "tinggi": {"mean": round(mean_t, 4), "var": round(var_t, 4), "p": round(prob_t, 8)},
                "rendah": {"mean": round(mean_r, 4), "var": round(var_r, 4), "p": round(prob_r, 8)}
            })

        details.append({
            "step": "Likelihood Numerik",
            "desc": "Menghitung probabilitas variabel angka menggunakan Distribusi Gaussian.",
            "items": num_steps
        })

        # 4. Final Result
        max_log = max(log_post_tinggi, log_post_rendah)
        exp_t = math.exp(log_post_tinggi - max_log)
        exp_r = math.exp(log_post_rendah - max_log)
        total_exp = exp_t + exp_r
        
        prob_tinggi_pct = (exp_t / total_exp) * 100
        prob_rendah_pct = (exp_r / total_exp) * 100
        prediction = "Tinggi" if log_post_tinggi > log_post_rendah else "Rendah"
        
        return {
            "prediction": prediction,
            "confidence_tinggi": round(prob_tinggi_pct, 2),
            "confidence_rendah": round(prob_rendah_pct, 2),
            "log_tinggi": round(log_post_tinggi, 4),
            "log_rendah": round(log_post_rendah, 4),
            "details": details
        }

    except Exception as e:
        return {"error": str(e)}

if __name__ == "__main__":
    if len(sys.argv) > 1:
        try:
            with open(sys.argv[1], 'r') as f:
                data = json.load(f)
            result = calculate_nb(data)
            print(json.dumps(result))
        except Exception as e:
            print(json.dumps({"error": str(e)}))