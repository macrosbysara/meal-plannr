export default function BlockPreview( { attributes } ) {
	const {
		name,
		quantityVolume,
		unitVolume,
		quantityWeight,
		unitWeight,
		description,
	} = attributes;
	return (
		<>
			{ 0 !== quantityWeight && (
				<span>
					{ quantityWeight } { unitWeight }
				</span>
			) }
			{ 0 !== quantityVolume && (
				<span>
					{ quantityVolume } { unitVolume }
				</span>
			) }
			<span>{ name }</span>
			{ description && <span>{ description }</span> }
		</>
	);
}
